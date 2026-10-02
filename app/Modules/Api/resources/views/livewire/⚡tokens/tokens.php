<?php

use App\Modules\Api\Services\ApiAccess;
use App\Modules\Api\Services\TokenIssuer;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Account → API tokens: a user's personal tokens. Only reachable when the
 * access policy (Backoffice → API Access) lets them create tokens, or they
 * hold a token an admin issued them. Creating is limited to the abilities,
 * lifetime and count the policy allows; the plain token is shown once.
 */
new #[Layout('layouts.accounts')] class extends Component
{
    public string $name = '';

    /** @var array<int, string> */
    public array $abilities = [];

    /** Days until expiry; '' = never (only when the policy has no limit). */
    public string $expiresIn = '';

    /** Shown once after creating a token, then forgotten. */
    public ?string $plainToken = null;

    public function mount(): void
    {
        abort_unless($this->canCreate() || auth()->user()->tokens()->exists(), 403);

        $this->abilities = array_slice(array_keys($this->abilityOptions()), 0, 1);
        $this->expiresIn = (string) (array_key_last($this->expiryOptions()) ?? '');
    }

    public function canCreate(): bool
    {
        return app(ApiAccess::class)->canSelfServe(auth()->user());
    }

    /** @return array<string, string> */
    public function abilityOptions(): array
    {
        return app(ApiAccess::class)->selfServiceAbilities();
    }

    /** @return array<string, string> days ('' = never) => label, within the policy's limit */
    public function expiryOptions(): array
    {
        $max = app(ApiAccess::class)->maxDays();

        return collect(config('api.token_expiry_days', []))
            ->filter(fn (?int $days) => $max === null || ($days !== null && $days <= $max))
            ->mapWithKeys(fn (?int $days) => [(string) ($days ?? '') => $days ? trans_choice(':count day|:count days', $days) : __('Never')])
            ->all();
    }

    public function tokens(): Collection
    {
        return auth()->user()->tokens()->with('issuer')->latest()->get();
    }

    public function remaining(): int
    {
        return max(0, app(ApiAccess::class)->maxTokens() - auth()->user()->tokens()->count());
    }

    public function create(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['required', 'array', 'min:1'],
        ]);

        if (auth()->user()->tokens()->where('name', $this->name)->exists()) {
            $this->addError('name', __('You already have a token with this name.'));

            return;
        }

        $token = app(TokenIssuer::class)->issueForSelf(
            auth()->user(),
            $this->name,
            array_values($this->abilities),
            $this->expiresIn === '' ? null : (int) $this->expiresIn,
        );

        $this->plainToken = $token->plainTextToken;
        $this->reset('name');
    }

    public function dismissToken(): void
    {
        $this->plainToken = null;
    }

    public function revoke(int $id): void
    {
        // Scoped to the signed-in user's own tokens.
        $token = auth()->user()->tokens()->whereKey($id)->first();

        if ($token !== null) {
            app(TokenIssuer::class)->revoke($token, auth()->user());
        }
    }

    public function apiBaseUrl(): string
    {
        return rtrim(route('api.'.config('api.version').'.index'), '/');
    }
};
