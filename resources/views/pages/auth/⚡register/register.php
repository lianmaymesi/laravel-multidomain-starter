<?php

use App\Enums\OtpType;
use App\Models\User;
use App\Services\Auth\OtpService;
use App\Services\Auth\SmsService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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

    #[Validate('required', message: 'Phone number is required')]
    public string $phone = '';

    public string $country_code = '+91';

    public function rules(): array
    {
        return [
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
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
        ];
    }

    public function register(OtpService $otpService, SmsService $smsService)
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($this->password),
            'phone' => $this->phone,
            'country_code' => $this->country_code,
        ]);

        event(new Registered($user));

        Auth::login($user);

        // Generate phone OTP and send SMS immediately
        $otp = $otpService->generate($user, OtpType::PHONE_VERIFICATION);
        $smsService->sendOtp($user->fullPhone(), $otp->code);

        $this->redirect(route('auth.verify-phone'), navigate: true);
    }
};
