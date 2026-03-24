<?php

use App\Services\Auth\OtpService;
use App\Services\Auth\SmsService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts.auth')] class extends Component
{
    #[Validate(['required', 'string', 'digits:6'])]
    public string $code = '';

    /** Remaining seconds before user can resend */
    public int $resendCooldown = 0;

    public function mount(OtpService $otpService): void
    {
        $this->resendCooldown = $otpService->resendCooldown(
            Auth::user(),
            OtpService::PHONE_VERIFICATION
        );
    }

    public function verify(OtpService $otpService): void
    {
        $this->validate();

        $user = Auth::user();

        // Will throw ValidationException on failure
        $otpService->validate($user, OtpService::PHONE_VERIFICATION, $this->code);

        $user->markPhoneAsVerified();

        // Redirect to 2FA setup if enabled/required, otherwise go to panel
        if ($user->hasTwoFactorEnabled()) {
            $this->redirect(route('auth.two-factor-challenge'), navigate: true);
            return;
        }

        $this->redirect($user->redirectSubdomain(), navigate: false);
    }

    public function resend(OtpService $otpService, SmsService $smsService): void
    {
        $user = Auth::user();

        $otpService->gateResend($user, OtpService::PHONE_VERIFICATION);

        $otp = $otpService->generate($user, OtpService::PHONE_VERIFICATION);
        $smsService->sendOtp($user->fullPhone(), $otp->code);

        $this->resendCooldown = 60;

        session()->flash('status', 'A new code has been sent to your phone.');
    }
};
