<?php

use App\Services\Auth\TwoFactorService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts.auth')] class extends Component
{
    #[Validate(['required|digits:6'])]
    public string $code = '';

    public string $qrCodeSvg = '';

    public bool $confirmed = false;

    public array $recoveryCodes = [];

    public function mount(TwoFactorService $twoFactor): void
    {
        $user = Auth::user();

        // Generate or reuse existing secret
        if (! $user->two_factor_secret) {
            $twoFactor->generateSecret($user);
        }

        $this->qrCodeSvg = $twoFactor->qrCodeSvg($user);
    }

    public function confirm(TwoFactorService $twoFactor): void
    {
        $this->validate();

        $user = Auth::user();

        if (! $twoFactor->confirm($user, $this->code)) {
            $this->addError('code', 'Invalid code. Please scan the QR again and try.');
            return;
        }

        $this->confirmed     = true;
        $this->recoveryCodes = $user->fresh()->twoFactorRecoveryCodes();
    }

    public function skip(): void
    {
        $this->redirect(Auth::user()->redirectSubdomain(), navigate: false);
    }
};
