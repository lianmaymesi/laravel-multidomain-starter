<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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

    /**
     * @return \Illuminate\Support\Collection<int, object>
     */
    public function sessions(): \Illuminate\Support\Collection
    {
        return DB::table('sessions')
            ->where('user_id', Auth::id())
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($session) {
                $session->is_current = $session->id === session()->getId();

                return $session;
            });
    }

    public function logoutSession(string $sessionId): void
    {
        if ($sessionId === session()->getId()) {
            return;
        }

        DB::table('sessions')
            ->where('user_id', Auth::id())
            ->where('id', $sessionId)
            ->delete();

        $this->rotateRememberToken();
    }

    public function logoutOtherSessions(): void
    {
        DB::table('sessions')
            ->where('user_id', Auth::id())
            ->where('id', '!=', session()->getId())
            ->delete();

        $this->rotateRememberToken();
    }

    /**
     * Rotating the remember token invalidates "remember me" cookies on every
     * other device, so a deleted session can't silently re-authenticate itself.
     * If this device also has a remember cookie, it's reissued with the new
     * token so the current device stays logged in.
     */
    private function rotateRememberToken(): void
    {
        $user = Auth::user();
        $hadRecaller = request()->cookies->has(Auth::getRecallerName());

        $user->forceFill(['remember_token' => Str::random(60)])->save();

        if ($hadRecaller) {
            Auth::login($user, true);
        }
    }

    /**
     * @return array{label: string, icon: string}
     */
    public function describeUserAgent(?string $userAgent): array
    {
        $userAgent = $userAgent ?? '';

        $platform = match (true) {
            (bool) preg_match('/iPhone|iPad/i', $userAgent) => 'iOS',
            (bool) preg_match('/Android/i', $userAgent)     => 'Android',
            (bool) preg_match('/Macintosh/i', $userAgent)   => 'macOS',
            (bool) preg_match('/Windows/i', $userAgent)     => 'Windows',
            (bool) preg_match('/Linux/i', $userAgent)       => 'Linux',
            default                                         => 'Unknown device',
        };

        $browser = match (true) {
            (bool) preg_match('/Edg\//i', $userAgent)               => 'Edge',
            (bool) preg_match('/OPR\/|Opera/i', $userAgent)         => 'Opera',
            (bool) preg_match('/Chrome\//i', $userAgent)            => 'Chrome',
            (bool) preg_match('/CriOS/i', $userAgent)               => 'Chrome',
            (bool) preg_match('/Firefox\//i', $userAgent)           => 'Firefox',
            (bool) preg_match('/Safari\//i', $userAgent)            => 'Safari',
            default                                                 => 'Unknown browser',
        };

        $isMobile = (bool) preg_match('/iPhone|Android|Mobile/i', $userAgent);

        return [
            'label' => "{$browser} on {$platform}",
            'icon'  => $isMobile ? 'device-phone-mobile' : 'computer-desktop',
        ];
    }
};
