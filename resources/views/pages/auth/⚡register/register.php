<?php

use App\Enums\OtpType;
use App\Models\User;
use App\Services\Auth\OtpService;
use App\Contracts\SmsService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts.auth')] class extends Component
{
    #[Validate('required', message: 'Name is required')]
    #[Validate('string', message: 'Name must be a alphabetical letters')]
    #[Validate('max:255', message: 'Name is too long')]
    public string $name = '';

    #[Validate('required', message: 'Email is required')]
    #[Validate('email', message: 'Invalid email')]
    #[Validate('max:255', message: 'Email is too long')]
    #[Validate('unique:users,email', message: 'This email is already exists')]
    public string $email = '';

    public string $password = '';

    #[Validate('required', message: 'Confirmed Password is required')]
    public string $password_confirmation = '';

    public string $phone = '';

    public string $country_code = '';

    public function mount(): void
    {
        $this->country_code = config('multidomain.phone_default_country_code');
    }

    public function rules(): array
    {
        $rules = [
            'password' => ['required', 'confirmed', Password::defaults()],
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
            'password.required' => 'Password is required',
            'password.confirmed' => 'Passwords do not match',
            'password.letters' => 'The Password must contain at least one letter.',
            'password.mixed' => 'The Password must contain at least one uppercase and one lowercase letter.',
            'password.numbers' => 'The Password must contain at least one number.',
            'password.symbols' => 'The Password must contain at least one special character.',
            'password.uncompromised' => 'Please chose a stronger password.',
            'phone.required' => 'Phone number is required',
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
