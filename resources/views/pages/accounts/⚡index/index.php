<?php

use App\Enums\OtpType;
use App\Models\AccountDeletionRequest;
use App\Notifications\PendingEmailVerification;
use App\Notifications\VerifyEmail;
use App\Services\AccountDeletionService;
use App\Services\Auth\OtpService;
use App\Contracts\SmsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.accounts')] class extends Component
{
    // ── Profile section ───────────────────────────────────────────────
    public bool $editingName = false;
    public string $name = '';

    // ── Contact section — email ───────────────────────────────────────
    public bool $editingEmail = false;
    public string $newEmail = '';

    // ── Contact section — phone ───────────────────────────────────────
    public bool $editingPhone = false;
    public string $newPhone = '';

    // ── Delete account ────────────────────────────────────────────────
    public bool $showDeleteConfirm = false;
    public string $deletePassword = '';
    public ?AccountDeletionRequest $deletionRequest = null;

    // ─────────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->name           = Auth::user()->name;
        $this->deletionRequest = Auth::user()->activeDeletionRequest();
    }

    // ── Name ──────────────────────────────────────────────────────────

    public function editName(): void
    {
        $this->cancelAll();
        $this->name        = Auth::user()->name;
        $this->editingName = true;
        $this->dispatch('focus-name-input');
    }

    public function cancelName(): void
    {
        $this->resetValidation();
        $this->name        = Auth::user()->name;
        $this->editingName = false;
    }

    public function saveName(): void
    {
        $this->validate(['name' => ['required', 'string', 'max:255']]);

        Auth::user()->forceFill(['name' => $this->name])->save();

        $this->editingName = false;
    }

    // ── Email ─────────────────────────────────────────────────────────

    public function editEmail(): void
    {
        $this->cancelAll();
        $this->newEmail      = '';
        $this->editingEmail  = true;
        $this->dispatch('focus-email-input');
    }

    public function cancelEmail(): void
    {
        $this->resetValidation();
        $this->newEmail     = '';
        $this->editingEmail = false;
    }

    public function requestEmailChange(): void
    {
        $user = Auth::user();

        $this->validate([
            'newEmail' => [
                'required',
                'email',
                'different:' . $user->email,
                Rule::unique('users', 'email')->ignore($user->id),
                Rule::unique('users', 'pending_email')->ignore($user->id),
            ],
        ]);

        $token = Str::random(64);

        $user->forceFill([
            'pending_email'              => $this->newEmail,
            'pending_email_token'        => $token,
            'pending_email_requested_at' => now(),
        ])->save();

        $user->notify(new PendingEmailVerification($token, $this->newEmail));

        $this->newEmail     = '';
        $this->editingEmail = false;
    }

    public function sendEmailVerification(OtpService $otpService): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            return;
        }

        $otp = $otpService->generate($user, OtpType::EMAIL_VERIFICATION);
        $user->notify(new VerifyEmail($otp->code));

        $this->redirect(route('auth.verify-email'), navigate: false);
    }

    public function cancelEmailChange(): void
    {
        Auth::user()->forceFill([
            'pending_email'              => null,
            'pending_email_token'        => null,
            'pending_email_requested_at' => null,
        ])->save();
    }

    public function resendEmailVerification(): void
    {
        $user = Auth::user()->fresh();

        if (! $user->hasPendingEmailChange()) {
            return;
        }

        $token = Str::random(64);

        $user->forceFill([
            'pending_email_token'        => $token,
            'pending_email_requested_at' => now(),
        ])->save();

        $user->notify(new PendingEmailVerification($token, $user->pending_email));

        session()->flash('emailStatus', 'Verification email resent.');
    }

    // ── Phone ─────────────────────────────────────────────────────────

    public function editPhone(): void
    {
        if (! config('multidomain.phone_verification_enabled')) {
            return;
        }

        $this->cancelAll();
        $this->newPhone     = '';
        $this->editingPhone = true;
        $this->dispatch('focus-phone-input');
    }

    public function cancelPhone(): void
    {
        $this->resetValidation();
        $this->newPhone     = '';
        $this->editingPhone = false;
    }

    public function savePhone(OtpService $otpService, SmsService $smsService): void
    {
        if (! config('multidomain.phone_verification_enabled')) {
            return;
        }

        $this->validate([
            'newPhone' => ['required', 'string', 'regex:/^\d{10}$/'],
        ]);

        $user = Auth::user();

        $user->forceFill([
            'phone'             => $this->newPhone,
            'phone_verified_at' => null,
        ])->save();

        $otp = $otpService->generate($user, OtpType::PHONE_VERIFICATION);
        $smsService->sendOtp($user->fullPhone(), $otp->code);

        $this->editingPhone = false;

        $this->redirect(route('auth.verify-phone'), navigate: false);
    }

    // ─────────────────────────────────────────────────────────────────

    // ── Delete account ────────────────────────────────────────────────

    public function requestDeletion(): void
    {
        $this->validate([
            'deletePassword' => ['required', 'current_password'],
        ], [
            'deletePassword.current_password' => 'The password you entered is incorrect.',
        ]);

        app(AccountDeletionService::class)->request(Auth::user());

        $this->reset('deletePassword', 'showDeleteConfirm');

        // Sessions already revoked in service; log out current session and redirect
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();
        session()->flash('deletion_requested', true);

        $this->redirect(route('auth.login'), navigate: false);
    }

    public function cancelDeletion(): void
    {
        if ($this->deletionRequest?->isCancellable()) {
            app(AccountDeletionService::class)->cancel($this->deletionRequest);
            $this->deletionRequest = null;
        }
    }

    // ─────────────────────────────────────────────────────────────────

    private function cancelAll(): void
    {
        $this->editingName       = false;
        $this->editingEmail      = false;
        $this->editingPhone      = false;
        $this->showDeleteConfirm = false;
        $this->resetValidation();
    }
};
