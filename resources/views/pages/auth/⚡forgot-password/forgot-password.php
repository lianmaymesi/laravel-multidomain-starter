<?php

use App\Enums\OtpType;
use App\Models\User;
use App\Services\Auth\OtpService;
use App\Services\Auth\SmsService;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate(['required', 'digits:10'])]
    public string $phone = '';

    #[Validate(['required', 'string', 'max:3'])]
    public string $country_code = '+91';

    public bool $otpSent = false;

    public function sendOtp(OtpService $otpService, SmsService $smsService): void
    {
        $this->validate();

        $user = User::where('phone', $this->phone)
            ->where('country_code', $this->country_code)
            ->first();

        // Silent fail — don't reveal if number exists
        if ($user) {
            $otp = $otpService->generate($user, OtpType::PASSWORD_RESET);
            $smsService->sendOtp($user->fullPhone(), $otp->code);

            // Store user id in session for the reset step
            session(['pwd_reset_user' => $user->id]);
        }

        $this->otpSent = true;
        session()->flash('status', 'If this number is registered, you will receive a code shortly.');
    }
};
