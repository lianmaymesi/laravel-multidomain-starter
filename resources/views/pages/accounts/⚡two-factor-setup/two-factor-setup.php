<?php

use App\Services\Auth\TwoFactorService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts.accounts')] class extends Component
{
    #[Validate('required', message: 'Code is required')]
    #[Validate('digits:6', message: 'Code must be 6 digits')]
    public string $code = '';

    public string $qrCodeSvg = '';

    public bool $confirmed = false;

    public bool $justConfirmed = false;

    public bool $showRecoveryCodes = false;

    public array $recoveryCodes = [];

    public function mount(TwoFactorService $twoFactor): void
    {
        $user = Auth::user();

        if ($user->hasTwoFactorEnabled()) {
            $this->confirmed = true;
            return;
        }

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

        $this->confirmed      = true;
        $this->justConfirmed  = true;
        $this->showRecoveryCodes = true;
        $this->recoveryCodes  = $user->fresh()->twoFactorRecoveryCodes();
    }

    public function viewRecoveryCodes(): void
    {
        $this->recoveryCodes     = Auth::user()->twoFactorRecoveryCodes();
        $this->showRecoveryCodes = true;
    }

    public function hideRecoveryCodes(): void
    {
        $this->showRecoveryCodes = false;
        $this->recoveryCodes     = [];
    }

    public function regenerateRecoveryCodes(TwoFactorService $twoFactor): void
    {
        $twoFactor->regenerateRecoveryCodes(Auth::user());
        $this->recoveryCodes     = Auth::user()->fresh()->twoFactorRecoveryCodes();
        $this->showRecoveryCodes = true;
    }

    public function disable(TwoFactorService $twoFactor): void
    {
        $twoFactor->disable(Auth::user());
        $twoFactor->generateSecret(Auth::user());

        $this->confirmed         = false;
        $this->justConfirmed     = false;
        $this->showRecoveryCodes = false;
        $this->recoveryCodes     = [];
        $this->qrCodeSvg         = $twoFactor->qrCodeSvg(Auth::user()->fresh());
    }

    public function reconfigure(TwoFactorService $twoFactor): void
    {
        $twoFactor->disable(Auth::user());
        $twoFactor->generateSecret(Auth::user());

        $this->confirmed         = false;
        $this->justConfirmed     = false;
        $this->showRecoveryCodes = false;
        $this->recoveryCodes     = [];
        $this->qrCodeSvg         = $twoFactor->qrCodeSvg(Auth::user()->fresh());
    }

    public function skip(): void
    {
        $this->redirect(Auth::user()->redirect(), navigate: false);
    }
};
