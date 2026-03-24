<?php

use App\Models\User;
use App\Services\Auth\OtpService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts.auth')] class extends Component
{
    #[Validate('required|digits:6')]
    public string $code = '';

    #[Validate(['required', 'confirmed', Password::defaults()])]
    public string $password = '';

    public string $password_confirmation = '';

    protected function rules(): array
    {
        return [
            'code'     => ['required', 'digits:6'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function mount(): void
    {
        // If there's no pending reset session, bounce back
        if (! session('pwd_reset_user')) {
            $this->redirect(route('auth.forgot-password'), navigate: true);
        }
    }

    public function reset(OtpService $otpService): void
    {
        $this->validate();

        $userId = session('pwd_reset_user');
        $user   = User::findOrFail($userId);

        // Will throw ValidationException on bad code
        $otpService->validate($user, OtpService::PASSWORD_RESET, $this->code);

        $user->forceFill([
            'password' => Hash::make($this->password),
        ])->save();

        session()->forget('pwd_reset_user');

        Auth::login($user);

        $this->redirect($user->redirectSubdomain(), navigate: false);
    }
};
