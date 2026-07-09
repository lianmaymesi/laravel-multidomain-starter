<?php

use App\Enums\OtpType;
use App\Models\User;
use App\Notifications\ForgotPassword;
use App\Services\Auth\OtpService;
use App\Services\Auth\SmsService;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.auth')] class extends Component
{
    /** Which tab is active: 'phone' | 'email' */
    public string $activeTab = 'phone';

    /** Phone fields */
    public string $country_code = '+91';
    public string $phone = '';

    /** Email field */
    public string $email = '';

    /** OTP code input */
    public string $code = '';

    /** Whether OTP has been sent and we're on step 2 */
    public bool $otpSent = false;

    /** Countdown seconds for resend button */
    public int $resendCooldown = 0;

    /** Masked email shown on step 2 (e.g. j***@example.com) */
    public string $maskedEmail = '';

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->reset(['phone', 'email', 'code']);
        $this->resetValidation();
    }

    public function sendOtp(OtpService $otpService, SmsService $smsService): void
    {
        $otpType   = $this->otpType();
        $recipient = $this->resendRecipient();

        if ($this->activeTab === 'phone') {
            $this->validate([
                'country_code' => ['required', 'string'],
                'phone' => ['required', 'string'],
            ]);

            $user = User::where('phone', Str::replace('-', '', $this->phone))
                ->where('country_code', $this->country_code)
                ->first();
        } else {
            $this->validate([
                'email' => ['required', 'email'],
            ]);

            $user = User::where('email', $this->email)->first();

            [$local, $domain] = explode('@', $this->email);
            $this->maskedEmail = substr($local, 0, 1) . '***@' . $domain;
        }

        $otpService->gateResend($recipient, $otpType);

        // Always show success to prevent user enumeration
        if (!$user) {
            $this->otpSent = true;
            $this->resendCooldown = (int) config('verification.otp.resend_cooldown', 60);
            session()->flash('status', 'If that account exists, a code has been sent.');
            return;
        }

        if ($this->activeTab === 'phone') {
            $otp = $otpService->generate($user, $otpType);
            $smsService->sendOtp($user->fullPhone(), $otp->code);
        } else {
            $otp = $otpService->generate($user, $otpType);
            $user->notify(new ForgotPassword($otp->code));
        }

        $this->otpSent = true;
        $this->resendCooldown = (int) config('verification.otp.resend_cooldown', 60);

        session()->flash('status', 'A reset code has been sent.');
    }

    public function verifyOtp(OtpService $otpService): void
    {
        $this->validate([
            'code' => ['required', 'string', 'digits:6'],
        ]);

        if ($this->activeTab === 'phone') {
            $user = User::where('phone', Str::replace('-', '', $this->phone))
                ->where('country_code', $this->country_code)
                ->firstOrFail();
        } else {
            $user = User::where('email', $this->email)->firstOrFail();
        }

        // Will throw ValidationException on failure
        $otpService->validate($user, $this->otpType(), $this->code);

        // Generate a Laravel-standard password reset token and store it
        // in the `password_reset_tokens` table (default Laravel migration).
        // This replaces the custom session approach so the reset-password page
        // can validate identity using Password::tokenExists() — the same
        // pattern Laravel's built-in auth scaffolding uses.
        $token = Password::createToken($user);

        $this->redirect(
            route('auth.reset-password', array_filter([
                'token' => $token,
                'email' => $this->activeTab === 'email' ? $this->email : null,
                'phone' => $this->activeTab === 'phone' ? $this->phone : null,
            ])),
            navigate: true,
        );
    }

    public function resend(OtpService $otpService, SmsService $smsService): void
    {
        if ($this->activeTab === 'phone') {
            $user = User::where('phone', Str::replace('-', '', $this->phone))
                ->where('country_code', $this->country_code)
                ->first();
        } else {
            $user = User::where('email', $this->email)->first();
        }

        $otpType = $this->otpType();
        $recipient = $this->resendRecipient();

        $otpService->gateResend($recipient, $otpType);

        if (!$user) {
            // Silently ignore — don't leak whether the account exists
            $this->resendCooldown = (int) config('verification.otp.resend_cooldown', 60);
            return;
        }

        if ($this->activeTab === 'phone') {
            $otp = $otpService->generate($user, $otpType);
            $smsService->sendOtp($user->fullPhone(), $otp->code);
        } else {
            $otp = $otpService->generate($user, $otpType);
            $user->notify(new ForgotPassword($otp->code));
        }

        $this->resendCooldown = (int) config('verification.otp.resend_cooldown', 60);

        session()->flash('status', 'A new code has been sent.');
    }

    public function goBack(): void
    {
        $this->otpSent = false;
        $this->resendCooldown = 0;
        $this->code = '';
        $this->resetValidation();
    }

    private function otpType(): OtpType
    {
        return $this->activeTab === 'phone'
            ? OtpType::PHONE_FORGOT_PASSWORD
            : OtpType::EMAIL_FORGOT_PASSWORD;
    }

    private function resendRecipient(): string
    {
        if ($this->activeTab === 'phone') {
            return $this->country_code . preg_replace('/\D/', '', $this->phone);
        }

        return strtolower(trim($this->email));
    }
};
