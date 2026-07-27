<?php

namespace App\Services\Auth;

use App\Enums\OtpType;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OtpService
{
    /**
     * Generate and persist a fresh OTP for a user.
     * Invalidates all previous OTPs of the same type.
     */
    public function generate(User $user, OtpType $type, ?int $expiresInMinutes = null): OtpCode
    {
        OtpCode::where('user_id', $user->id)
            ->where('type', $type->value)
            ->where('used_at', null)
            ->update(['used_at' => now()]);

        $code = $this->makeCode();

        return OtpCode::create([
            'user_id' => $user->id,
            'type' => $type->value,
            'code' => $code,
            'expires_at' => now()->addMinutes($expiresInMinutes ?? $this->expiresMinutes()),
            'used_at' => null,
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * Validate a submitted OTP code.
     * Throws a ValidationException on failure so Livewire catches it as a field error.
     */
    public function validate(User $user, OtpType $type, string $submittedCode): void
    {
        $rateLimiterKey = "otp:{$type->value}:{$user->id}";

        if (RateLimiter::tooManyAttempts($rateLimiterKey, $this->maxAttempts())) {
            $seconds = RateLimiter::availableIn($rateLimiterKey);
            throw ValidationException::withMessages([
                'code' => "Too many attempts. Please wait {$seconds} seconds.",
            ]);
        }

        $otp = OtpCode::where('user_id', $user->id)
            ->where('type', $type->value)
            ->where('used_at', null)
            ->latest()
            ->first();

        if (! $otp || ! $otp->isValid() || ! hash_equals($otp->code, $submittedCode)) {
            RateLimiter::hit($rateLimiterKey, 300); // 5-minute decay
            throw ValidationException::withMessages([
                'code' => 'The code is invalid or has expired.',
            ]);
        }

        // Mark as used
        $otp->update(['used_at' => now()]);

        RateLimiter::clear($rateLimiterKey);
    }

    /**
     * Check how many seconds the user must wait before requesting a new OTP.
     */
    public function resendCooldown(User $user, OtpType $type): int
    {
        $key = $this->resendCooldownKey($user, $type);

        return RateLimiter::availableIn($key);
    }

    /**
     * Gate an OTP resend request.
     *
     * Allows 1 request per cooldown window and at most the configured number
     * of requests in the configured lockout window.
     */
    public function gateResend(User|string $recipient, OtpType $type): void
    {
        $lockoutKey = $this->resendLockoutKey($recipient, $type);
        $maxAttempts = $this->resendMaxAttempts();

        if (RateLimiter::tooManyAttempts($lockoutKey, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($lockoutKey);
            throw ValidationException::withMessages([
                'code' => 'You have reached the maximum number of OTP requests. '
                    .'Please try again in '.$this->secondsForHumans($seconds).'.',
            ]);
        }

        $cooldownKey = $this->resendCooldownKey($recipient, $type);

        if (RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            $seconds = RateLimiter::availableIn($cooldownKey);
            throw ValidationException::withMessages([
                'code' => "You can request a new code in {$seconds} seconds.",
            ]);
        }

        RateLimiter::hit($lockoutKey, $this->resendLockoutSeconds());
        RateLimiter::hit($cooldownKey, $this->resendCooldownSeconds());
    }

    /**
     * Generate a random 6-digit code.
     */
    private function makeCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    private function resendCooldownKey(User|string $recipient, OtpType $type): string
    {
        return "otp_resend_cooldown:{$type->value}:{$this->resendRecipientKey($recipient)}";
    }

    private function resendLockoutKey(User|string $recipient, OtpType $type): string
    {
        return "otp_resend_lockout:{$type->value}:{$this->resendRecipientKey($recipient)}";
    }

    private function resendRecipientKey(User|string $recipient): string
    {
        if ($recipient instanceof User) {
            return 'user:'.$recipient->getKey();
        }

        return 'recipient:'.sha1(Str::lower(trim($recipient)));
    }

    private function expiresMinutes(): int
    {
        return (int) config('multidomain.otp.expires_minutes', 10);
    }

    private function maxAttempts(): int
    {
        return (int) config('multidomain.otp.max_attempts', 5);
    }

    private function resendCooldownSeconds(): int
    {
        return (int) config('multidomain.otp.resend_cooldown', 60);
    }

    private function resendLockoutSeconds(): int
    {
        return (int) config('multidomain.otp.resend_lockout_seconds', 86400);
    }

    private function resendMaxAttempts(): int
    {
        return (int) config('multidomain.otp.resend_max_attempts', 3);
    }

    private function secondsForHumans(int $seconds): string
    {
        if ($seconds >= 86400) {
            $hours = (int) ceil($seconds / 3600);

            return $hours === 24 ? '24 hours' : "{$hours} hours";
        }

        if ($seconds >= 3600) {
            $hours = (int) ceil($seconds / 3600);

            return $hours === 1 ? '1 hour' : "{$hours} hours";
        }

        if ($seconds >= 60) {
            $minutes = (int) ceil($seconds / 60);

            return $minutes === 1 ? '1 minute' : "{$minutes} minutes";
        }

        return $seconds === 1 ? '1 second' : "{$seconds} seconds";
    }
}
