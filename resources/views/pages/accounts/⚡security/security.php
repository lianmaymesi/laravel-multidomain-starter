<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.accounts')] class extends Component
{
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';
    public bool $passwordSuccess = false;

    public function updatePassword(): void
    {
        $this->validate([
            'current_password'      => ['required', 'current_password'],
            'password'              => ['required', 'confirmed', PasswordRule::defaults()],
            'password_confirmation' => ['required'],
        ]);

        Auth::user()->forceFill([
            'password' => Hash::make($this->password),
        ])->save();

        $this->reset('current_password', 'password', 'password_confirmation');
        $this->passwordSuccess = true;
    }

    public function updatedCurrentPassword(): void
    {
        $this->passwordSuccess = false;
    }
};
