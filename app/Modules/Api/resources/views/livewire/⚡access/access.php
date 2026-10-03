<?php

use App\Models\Role;
use App\Models\User;
use App\Modules\Api\Models\PersonalAccessToken;
use App\Modules\Api\Services\ApiAccess;
use App\Modules\Api\Services\TokenIssuer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Backoffice → API Access (Super Admin, api.manage):
 *
 *   1. Access policy — who may create their own tokens on Account → API
 *      tokens (nobody / staff / everyone / chosen roles), which abilities,
 *      how long, how many. Enforced on every API request.
 *   2. Issue a token to any user (integration accounts, partners) with any
 *      ability, including full access.
 *   3. Every token in the system — search, revoke one, or revoke them all.
 */
new #[Layout('layouts.backoffice')] class extends Component
{
    use WithPagination;

    // ── Policy ──
    public string $mode = 'none';

    /** @var array<int, string> role slugs */
    public array $roles = [];

    /** @var array<int, string> */
    public array $policyAbilities = [];

    /** '' = no limit */
    public string $maxDays = '';

    public int $maxTokens = 5;

    // ── Issue ──
    public string $issueEmail = '';

    public string $issueName = '';

    /** @var array<int, string> */
    public array $issueAbilities = [];

    public string $issueExpiresIn = '90';

    public ?string $issuedToken = null;

    public ?string $issuedFor = null;

    // ── Tokens ──
    public string $search = '';

    public ?string $status = null;

    public function mount(ApiAccess $access): void
    {
        abort_unless(Gate::allows('api.manage'), 403);

        $policy = $access->policy();
        $this->mode = $policy['self_service'];
        $this->roles = array_values($policy['roles']);
        $this->policyAbilities = array_values($policy['abilities']);
        $this->maxDays = (string) ($policy['max_days'] ?? '');
        $this->maxTokens = (int) $policy['max_tokens'];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /** @return array<string, string> */
    public function abilities(): array
    {
        return app(ApiAccess::class)->abilities();
    }

    public function roleOptions(): Collection
    {
        return Role::orderBy('name')->get(['slug', 'name']);
    }

    /** @return array<string, string> */
    public function expiryOptions(): array
    {
        return collect(config('api.token_expiry_days', []))
            ->mapWithKeys(fn (?int $days) => [(string) ($days ?? '') => $days ? trans_choice(':count day|:count days', $days) : __('Never')])
            ->all();
    }

    /** How many users the policy currently lets create tokens. */
    public function eligibleCount(): int
    {
        return match ($this->mode) {
            'everyone' => User::count(),
            'staff' => User::where('privilege', 'staff')->count(),
            'roles' => $this->roles === [] ? 0 : User::whereHas('roles', fn (Builder $query) => $query->whereIn('slug', $this->roles))->count(),
            default => 0,
        };
    }

    public function savePolicy(): void
    {
        abort_unless(Gate::allows('api.manage'), 403);

        $this->validate([
            'mode' => ['required', Rule::in(ApiAccess::MODES)],
            'roles' => [Rule::requiredIf($this->mode === 'roles'), 'array'],
            'roles.*' => [Rule::exists('roles', 'slug')],
            'policyAbilities' => ['array'],
            'policyAbilities.*' => [Rule::in(array_keys($this->abilities()))],
            'maxDays' => ['nullable', Rule::in(array_keys($this->expiryOptions()))],
            'maxTokens' => ['required', 'integer', 'min:1', 'max:100'],
        ]);

        $policy = [
            'self_service' => $this->mode,
            'roles' => $this->mode === 'roles' ? array_values($this->roles) : [],
            'abilities' => array_values($this->policyAbilities),
            'max_days' => $this->maxDays === '' ? null : (int) $this->maxDays,
            'max_tokens' => $this->maxTokens,
        ];

        app(ApiAccess::class)->savePolicy($policy);

        activity('api')->causedBy(auth()->user())->withProperties($policy)->log('api access policy updated');

        $this->status = __('Access policy saved. It applies to the next API request.');
    }

    public function issue(): void
    {
        abort_unless(Gate::allows('api.manage'), 403);

        $this->validate([
            'issueEmail' => ['required', 'email', Rule::exists('users', 'email')],
            'issueName' => ['required', 'string', 'max:100'],
            'issueAbilities' => ['required', 'array', 'min:1'],
            'issueAbilities.*' => [Rule::in([...array_keys($this->abilities()), '*'])],
            'issueExpiresIn' => ['nullable', Rule::in(array_keys($this->expiryOptions()))],
        ]);

        $owner = User::where('email', $this->issueEmail)->firstOrFail();

        $token = app(TokenIssuer::class)->issueFor(
            $owner,
            auth()->user(),
            $this->issueName,
            array_values($this->issueAbilities),
            $this->issueExpiresIn === '' ? null : (int) $this->issueExpiresIn,
        );

        $this->issuedToken = $token->plainTextToken;
        $this->issuedFor = $owner->email;
        $this->reset('issueName', 'issueAbilities');
    }

    public function dismissIssued(): void
    {
        $this->issuedToken = null;
        $this->issuedFor = null;
    }

    public function tokens(): LengthAwarePaginator
    {
        $term = '%'.str_replace(['%', '_'], '', trim($this->search)).'%';

        return PersonalAccessToken::query()
            ->with(['tokenable', 'issuer'])
            ->when(trim($this->search) !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('name', 'like', $term)
                ->orWhereHasMorph('tokenable', [User::class], fn (Builder $user) => $user
                    ->where('name', 'like', $term)
                    ->orWhere('email', 'like', $term))))
            ->latest()
            ->paginate(20);
    }

    public function stats(): array
    {
        return [
            'total' => PersonalAccessToken::count(),
            'used' => PersonalAccessToken::where('last_used_at', '>=', now()->subDays(30))->count(),
            'admin_issued' => PersonalAccessToken::whereColumn('issued_by', '!=', 'tokenable_id')->count(),
        ];
    }

    public function revoke(int $id): void
    {
        abort_unless(Gate::allows('api.manage'), 403);

        $token = PersonalAccessToken::find($id);

        if ($token !== null) {
            app(TokenIssuer::class)->revoke($token, auth()->user());
            $this->status = __('Token ":name" revoked.', ['name' => $token->name]);
        }
    }

    public function revokeAll(): void
    {
        abort_unless(Gate::allows('api.manage'), 403);

        $count = app(TokenIssuer::class)->revokeAll(auth()->user());

        $this->status = trans_choice(':count token revoked.|:count tokens revoked.', $count);
    }

    public function apiBaseUrl(): string
    {
        return rtrim(route('api.'.config('api.version').'.index'), '/');
    }
};
