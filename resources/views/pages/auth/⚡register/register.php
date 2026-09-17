<?php

use App\Enums\OtpType;
use App\Models\User;
use App\Services\Auth\OtpService;
use App\Contracts\SmsService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts.auth')] class extends Component
{
    #[Validate('required')]
    #[Validate('string')]
    #[Validate('max:255')]
    public string $name = '';

    #[Validate('required')]
    #[Validate('email')]
    #[Validate('max:255')]
    #[Validate('unique:users,email')]
    public string $email = '';

    public string $password = '';

    #[Validate('required')]
    public string $password_confirmation = '';

    public string $phone = '';

    public string $country_code = '';

    public string $userType = '';

    public function mount(): void
    {
        $this->country_code = config('multidomain.phone_default_country_code');
    }

    public function rules(): array
    {
        $rules = [
            'password' => ['required', 'confirmed', Password::defaults()],
            'userType' => ['nullable', Rule::in(array_keys(config('multidomain.registerable_portals', [])))],
        ];

        if (config('multidomain.phone_verification_enabled')) {
            $rules['phone'] = ['required', 'string'];

            if (config('multidomain.phone_country_mode') === 'multi') {
                $rules['country_code'] = ['required', 'string'];
            }
        }

        return $rules;
    }

    protected function messages()
    {
        return [
            'name.required' => __('Name is required'),
            'name.string' => __('Name must be a alphabetical letters'),
            'name.max' => __('Name is too long'),
            'email.required' => __('Email is required'),
            'email.email' => __('Invalid email'),
            'email.max' => __('Email is too long'),
            'email.unique' => __('This email is already exists'),
            'password_confirmation.required' => __('Confirmed Password is required'),
            'password.required' => __('Password is required'),
            'password.confirmed' => __('Passwords do not match'),
            'password.letters' => __('The Password must contain at least one letter.'),
            'password.mixed' => __('The Password must contain at least one uppercase and one lowercase letter.'),
            'password.numbers' => __('The Password must contain at least one number.'),
            'password.symbols' => __('The Password must contain at least one special character.'),
            'password.uncompromised' => __('Please chose a stronger password.'),
            'phone.required' => __('Phone number is required'),
        ];
    }

    public function register(OtpService $otpService, SmsService $smsService)
    {
        $this->validate();

        $phoneEnabled = config('multidomain.phone_verification_enabled');

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'phone' => $phoneEnabled ? Str::replace('-', '', $this->phone) : null,
            'country_code' => $phoneEnabled ? $this->country_code : null,
        ]);

        if ($this->userType !== '') {
            $user->assignRole($this->userType);
        }

        event(new Registered($user));

        Auth::login($user);

        if (! $phoneEnabled) {
            $this->redirect($user->redirect(), navigate: true);

            return;
        }

        // Generate phone OTP and send SMS immediately
        $otp = $otpService->generate($user, OtpType::PHONE_VERIFICATION);
        $smsService->sendOtp($user->fullPhone(), $otp->code);

        $this->redirect(route('auth.verify-phone'), navigate: true);
    }
};
