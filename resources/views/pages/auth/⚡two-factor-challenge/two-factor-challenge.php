<?php

use App\Models\User;
use Atrium\Core\Concerns\ThrottlesActions;
use Atrium\Core\Services\AccountDeletionService;
use Atrium\Core\Services\Auth\TwoFactorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts.auth')] class extends Component
{
    use ThrottlesActions;

    #[Validate(['required', 'string'])]
    public string $code = '';

    public bool $usingRecovery = false;

    protected array $rules = [
        'code' => ['required', 'string'],
    ];

    public function mount(): void
    {
        // Must have a pending 2FA session (set in Login component)
        if (! session('2fa_user_id')) {
            $this->redirect(route('auth.login'), navigate: true);
        }
    }

    public function verify(TwoFactorService $twoFactor): void
    {
        $this->validate();

        $user = User::findOrFail(session('2fa_user_id'));

        // A 6-digit code is guessable without this: limit failures per
        // account (whatever the IP) and per IP (whatever the account).
        $this->ensureNotThrottled('two-factor', (string) $user->id, 'code');
        $this->ensureNotThrottled('two-factor-ip', request()->ip(), 'code');

        $valid = $this->usingRecovery
            ? $twoFactor->verifyRecoveryCode($user, $this->code)
            : $twoFactor->verify($user, $this->code);

        if (! $valid) {
            $this->hitThrottle('two-factor', (string) $user->id);
            $this->hitThrottle('two-factor-ip', request()->ip());

            throw ValidationException::withMessages([
                'code' => $this->usingRecovery
                    ? __('Invalid recovery code.')
                    : __('Invalid authenticator code. Please try again.'),
            ]);
        }

        $this->clearThrottle('two-factor', (string) $user->id);

        session()->forget('2fa_user_id');

        Auth::login($user);

        // Auto-cancel pending deletion — completing 2FA during grace period cancels it
        $deletion = $user->activeDeletionRequest();
        if ($deletion?->isCancellable()) {
            app(AccountDeletionService::class)->cancel($deletion);
            session()->flash('deletion_cancelled', true);
            $this->redirect(route('account.index'), navigate: false);

            return;
        }

        $this->redirect($user->redirect(), navigate: false);
    }

    public function toggleRecovery(): void
    {
        $this->usingRecovery = ! $this->usingRecovery;
        $this->code = '';
        $this->resetErrorBag();
    }
};
