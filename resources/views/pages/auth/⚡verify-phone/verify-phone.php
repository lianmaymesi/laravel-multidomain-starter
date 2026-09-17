<?php

use App\Enums\OtpType;
use App\Services\Auth\OtpService;
use App\Contracts\SmsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts.auth')] class extends Component
{
    #[Validate('required')]
    #[Validate('numeric')]
    #[Validate('digits:6')]
    public int $code;

    /** Remaining seconds before user can resend */
    public int $resendCooldown = 0;

    /** How many resends the user has left (max 2) */
    public int $resendAttemptsLeft = 2;

    /** How many phone edits the user has left (max 2) */
    public int $editAttemptsLeft = 2;

    /** Whether the inline phone edit form is visible */
    public bool $editingPhone = false;

    public string $newCountryCode = '+91';

    #[Validate('required')]
    #[Validate('numeric')]
    #[Validate('digits:10')]
    public string $newPhone = '';

    protected function messages()
    {
        return [
            'code.required' => __('Code is required'),
            'code.numeric' => __('Invalid code'),
            'code.digits' => __('Code must be 6 digits'),
            'newPhone.required' => __('Phone number is required'),
            'newPhone.numeric' => __('Invalid phone number'),
            'newPhone.digits' => __('Invalid phone number'),
        ];
    }

    public function mount(OtpService $otpService): void
    {
        if (! config('multidomain.phone_verification_enabled')) {
            $this->redirect(Auth::user()->redirect(), navigate: false);

            return;
        }

        $this->resendCooldown = $otpService->resendCooldown(
            Auth::user(),
            OtpType::PHONE_VERIFICATION
        );
    }

    public function verify(OtpService $otpService): void
    {
        $this->validate([
            'code' => ['required', 'numeric', 'digits:6']
        ]);

        $user = Auth::user();

        // Will throw ValidationException on failure
        $otpService->validate($user, OtpType::PHONE_VERIFICATION, $this->code);

        $user->markPhoneAsVerified();

        if ($user->hasTwoFactorEnabled()) {
            $this->redirect(route('auth.two-factor-challenge'), navigate: true);
            return;
        }

        $this->redirect($user->redirect(), navigate: false);
    }

    public function resend(OtpService $otpService, SmsService $smsService): void
    {
        if ($this->resendAttemptsLeft <= 0) {
            throw ValidationException::withMessages([
                'code' => __('You have reached the maximum number of resend attempts.'),
            ]);
        }

        $user = Auth::user();

        $otpService->gateResend($user, OtpType::PHONE_VERIFICATION);

        $otp = $otpService->generate($user, OtpType::PHONE_VERIFICATION);
        $smsService->sendOtp($user->fullPhone(), $otp->code);

        $this->resendAttemptsLeft--;
        $this->resendCooldown = 60;

        session()->flash('status', __('A new code has been sent to your phone.'));
    }

    public function startEdit(): void
    {
        if ($this->editAttemptsLeft <= 0) {
            return;
        }

        $user = Auth::user();
        $this->newCountryCode = config('multidomain.phone_country_mode') === 'multi'
            ? ($user->country_code ?? config('multidomain.phone_default_country_code'))
            : config('multidomain.phone_default_country_code');
        // Don't pre-fill the actual phone for security — let user type it fresh
        $this->newPhone = '';
        $this->editingPhone = true;
    }

    public function cancelEdit(): void
    {
        $this->editingPhone = false;
        $this->reset(['newCountryCode', 'newPhone']);
        $this->resetValidation(['newCountryCode', 'newPhone']);
    }

    public function updatePhone(OtpService $otpService, SmsService $smsService): void
    {
        if ($this->editAttemptsLeft <= 0) {
            return;
        }

        $isMultiCountry = config('multidomain.phone_country_mode') === 'multi';

        $this->validate([
            'newCountryCode' => $isMultiCountry ? ['required', 'string'] : ['nullable', 'string'],
            'newPhone'       => ['required', 'string', 'regex:/^\d{10}$/'],
        ]);

        $user = Auth::user();

        // Update phone on the user record
        $user->country_code = $isMultiCountry ? $this->newCountryCode : config('multidomain.phone_default_country_code');
        $user->phone        = $this->newPhone;
        $user->save();

        // Generate and send a fresh OTP to the new number
        $otp = $otpService->generate($user, OtpType::PHONE_VERIFICATION);
        $smsService->sendOtp($user->fullPhone(), $otp->code);

        $this->editAttemptsLeft--;
        $this->resendCooldown = 60;
        $this->code = 0;
        $this->editingPhone = false;
        $this->reset(['newCountryCode', 'newPhone']);

        session()->flash('status', __('Phone updated. A new code has been sent.'));
    }
};
