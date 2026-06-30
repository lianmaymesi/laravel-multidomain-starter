<?php

use App\Enums\OtpType;
use App\Services\AccountDeletionService;
use App\Services\Auth\OtpService;
use App\Services\Auth\SmsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts.auth')] class extends Component
{
    #[Validate(['required', 'email'])]
    public string $email = '';

    #[Validate(['required', 'string'])]
    public string $password = '';

    public bool $remember = false;

    public function login(OtpService $otpService, SmsService $smsService): void
    {
        $this->validate();

        $this->ensureNotRateLimited();

        if (!Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($this->throttleKey(), 300);

            throw ValidationException::withMessages([
                'email' => 'These credentials do not match our records.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        $user = Auth::user();

        // Phone not verified → send OTP, redirect to verify
        if (!$user->hasVerifiedPhone()) {
            $otp = $otpService->generate($user, OtpType::PHONE_VERIFICATION);
            $smsService->sendOtp($user->fullPhone(), $otp->code);

            $this->redirect(route('auth.verify-phone'), navigate: true);
            return;
        }

        // 2FA enabled → challenge
        if ($user->hasTwoFactorEnabled()) {
            // Store a pending flag so the challenge knows the user authenticated
            session(['2fa_user_id' => $user->id]);
            Auth::logout(); // log out until 2FA passed

            $this->redirect(route('auth.two-factor-challenge'), navigate: true);
            return;
        }

        // Auto-cancel pending deletion — logging in during the grace period cancels it
        $deletion = $user->activeDeletionRequest();
        if ($deletion?->isCancellable()) {
            app(AccountDeletionService::class)->cancel($deletion);
            session()->flash('deletion_cancelled', true);
            $this->redirect(route('account.index'), navigate: false);
            return;
        }

        // Fully authenticated → redirect by role
        $this->redirect($user->redirect(), navigate: false);
    }

    private function ensureNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => "Too many login attempts. Please wait {$seconds} seconds.",
        ]);
    }

    private function throttleKey(): string
    {
        return 'login:' . strtolower($this->email) . '|' . request()->ip();
    }
};
