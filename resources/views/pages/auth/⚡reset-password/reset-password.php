<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts.auth')] class extends Component
{
    /** The signed token from the URL — written by Password::createToken() after OTP verification */
    #[Locked]
    public string $token = '';

    /** Identifies the account: one of these will be populated from the URL */
    #[Locked]
    #[Url]
    public string $email = '';

    #[Locked]
    #[Url]
    public string $phone = '';

    public string $password = '';
    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        // Bounce if token is missing — mirrors Laravel's built-in pattern
        if (blank($token)) {
            $this->redirect(route('auth.forgot-password'), navigate: true);
            return;
        }

        // Resolve the user from whichever identifier was passed
        $user = $this->resolveUser($this->email, $this->phone);

        // Validate the token against `password_reset_tokens` exactly as
        // Laravel's own PasswordBroker does. Invalid / expired → bounce back.
        if (!$user || !Password::tokenExists($user, $token)) {
            session()->flash('error', __('This password reset link is invalid or has expired.'));
            $this->redirect(route('auth.forgot-password'), navigate: true);
            return;
        }

        $this->token = $token;
    }

    public function resetPassword(): void
    {
        $this->validate([
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $user = $this->resolveUser($this->email ?: null, $this->phone ?: null);

        if (!$user) {
            $this->addError('password', __('Unable to locate the account. Please restart the reset flow.'));
            return;
        }

        // Double-check the token is still valid (guards against concurrent requests)
        if (!Password::tokenExists($user, $this->token)) {
            session()->flash('error', __('This reset link has already been used or has expired.'));
            $this->redirect(route('auth.forgot-password'), navigate: true);
            return;
        }

        // Update the password
        $user->forceFill([
            'password' => Hash::make($this->password),
        ])->save();

        // Delete the token from `password_reset_tokens` so it cannot be reused
        Password::deleteToken($user);

        $this->redirect(route('auth.login'), navigate: false);
    }

    /**
     * Look up the user by email or phone, whichever was supplied.
     * Phone numbers arrive without the country-code prefix in this flow,
     * so we match on the raw phone column only; adjust if your schema differs.
     */
    private function resolveUser(?string $email, ?string $phone): ?User
    {
        if (filled($email)) {
            return User::where('email', $email)->first();
        }

        if (filled($phone)) {
            return User::where('phone', preg_replace('/\D/', '', $phone))->first();
        }

        return null;
    }
};
