<?php

use App\Enums\OtpType;
use App\Notifications\VerifyEmail;
use App\Services\Auth\OtpService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts.auth')] class extends Component
{
    #[Validate('required')]
    #[Validate('digits:6')]
    public string $code = '';

    public int $daysLeft = 0;

    protected function messages()
    {
        return [
            'code.required' => __('Code is required'),
            'code.digits' => __('Code must be 6 digits'),
        ];
    }

    public function mount(): void
    {
        $user = Auth::user();

        // Already verified — skip this page
        if ($user->hasVerifiedEmail()) {
            $this->redirect($user->redirect(), navigate: false);
            return;
        }

        $this->daysLeft = $user->emailVerificationDeadlineDaysLeft();
    }

    public function verify(OtpService $otpService): void
    {
        $this->validate();

        $user = Auth::user();

        $otpService->validate($user, OtpType::EMAIL_VERIFICATION, $this->code);

        $user->markEmailAsVerified();

        $this->redirect($user->redirect(), navigate: false);
    }

    public function resend(OtpService $otpService): void
    {
        $user = Auth::user();

        $otpService->gateResend($user, OtpType::EMAIL_VERIFICATION);

        $otp = $otpService->generate($user, OtpType::EMAIL_VERIFICATION);

        $user->notify(new VerifyEmail($otp->code));

        session()->flash('status', __('A new verification code has been sent to your email.'));
    }
};
