<?php

use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Account → API tokens. Personal access tokens (Sanctum) for the /v1 API:
 * pick a name, abilities and expiry; the plain token is shown once, right
 * after creating it. A token never grants more than its user's own
 * permissions — abilities only narrow what it may do.
 */
new #[Layout('layouts.accounts')] class extends Component
{
    public string $name = '';

    /** @var array<int, string> */
    public array $abilities = ['read'];

    /** Days until expiry; '' = never. */
    public string $expiresIn = '90';

    /** Shown once after creating a token, then forgotten. */
    public ?string $plainToken = null;

    public function tokens(): Collection
    {
        return auth()->user()->tokens()->latest()->get();
    }

    /** @return array<string, string> */
    public function abilityOptions(): array
    {
        return config('api.abilities', []);
    }

    public function create(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('personal_access_tokens', 'name')
                ->where('tokenable_type', auth()->user()->getMorphClass())
                ->where('tokenable_id', auth()->id())],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => [Rule::in(array_keys($this->abilityOptions()))],
            'expiresIn' => ['nullable', Rule::in(array_map(fn ($days) => (string) ($days ?? ''), config('api.token_expiry_days', [])))],
        ]);

        $token = auth()->user()->createToken(
            $this->name,
            array_values($this->abilities),
            $this->expiresIn === '' ? null : now()->addDays((int) $this->expiresIn),
        );

        $this->plainToken = $token->plainTextToken;
        $this->reset('name');
        $this->abilities = ['read'];
    }

    public function dismissToken(): void
    {
        $this->plainToken = null;
    }

    public function revoke(int $id): void
    {
        // Scoped to the signed-in user's own tokens.
        auth()->user()->tokens()->whereKey($id)->delete();
    }

    public function apiBaseUrl(): string
    {
        return rtrim(route('api.'.config('api.version').'.index'), '/');
    }
};
