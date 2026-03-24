<?php

use App\Models\User;
use App\Services\Auth\TwoFactorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts.auth')] class extends Component
{
    #[Validate(['required', 'string'])]
    public string $code = '';

    public bool $usingRecovery = false;

    protected array $rules = [
        'code' => ['required', 'string'],
    ];

    public function mount(): void
    {
        // Must have a pending 2FA session (set in Login component)
        if (! session('2fa_user_id')) {
            $this->redirect(route('auth.login'), navigate: true);
        }
    }

    public function verify(TwoFactorService $twoFactor): void
    {
        $this->validate();

        $user = User::findOrFail(session('2fa_user_id'));

        $valid = $this->usingRecovery
            ? $twoFactor->verifyRecoveryCode($user, $this->code)
            : $twoFactor->verify($user, $this->code);

        if (! $valid) {
            throw ValidationException::withMessages([
                'code' => $this->usingRecovery
                    ? 'Invalid recovery code.'
                    : 'Invalid authenticator code. Please try again.',
            ]);
        }

        session()->forget('2fa_user_id');

        Auth::login($user);

        $this->redirect($user->redirectSubdomain(), navigate: false);
    }

    public function toggleRecovery(): void
    {
        $this->usingRecovery = ! $this->usingRecovery;
        $this->code = '';
        $this->resetErrorBag();
    }
};
