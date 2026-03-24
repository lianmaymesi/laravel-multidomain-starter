<?php

namespace App\Services\Auth;

use App\Enums\OtpType;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class OtpService
{
    /**
     * Generate and persist a fresh OTP for a user.
     * Invalidates all previous OTPs of the same type.
     */
    public function generate(User $user, OtpType $type, int $expiresInMinutes = 10): OtpCode
    {
        OtpCode::where('user_id', $user->id)
            ->where('type', $type->value)
            ->where('used', false)
            ->update(['used' => true]);

        $code = $this->makeCode();

        return OtpCode::create([
            'user_id' => $user->id,
            'type' => $type->value,
            'code' => $code,
            'expires_at' => now()->addMinutes($expiresInMinutes),
            'used' => false,
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

        if (RateLimiter::tooManyAttempts($rateLimiterKey, 5)) {
            $seconds = RateLimiter::availableIn($rateLimiterKey);
            throw ValidationException::withMessages([
                'code' => "Too many attempts. Please wait {$seconds} seconds.",
            ]);
        }

        $otp = OtpCode::where('user_id', $user->id)
            ->where('type', $type->value)
            ->where('used', false)
            ->latest()
            ->first();

        if (! $otp || ! $otp->isValid() || ! hash_equals($otp->code, $submittedCode)) {
            RateLimiter::hit($rateLimiterKey, 300); // 5-minute decay
            throw ValidationException::withMessages([
                'code' => 'The code is invalid or has expired.',
            ]);
        }

        // Mark as used
        $otp->update(['used' => true]);

        RateLimiter::clear($rateLimiterKey);
    }

    /**
     * Check how many seconds the user must wait before requesting a new OTP.
     */
    public function resendCooldown(User $user, OtpType $type): int
    {
        $key = "otp_resend:{$type->value}:{$user->id}";

        return RateLimiter::availableIn($key);
    }

    /**
     * Gate a resend request (1 per 60 seconds, max 5 per hour).
     */
    public function gateResend(User $user, OtpType $type): void
    {
        $key = "otp_resend:{$type->value}:{$user->id}";

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'code' => "You can request a new code in {$seconds} seconds.",
            ]);
        }

        RateLimiter::hit($key, 3600); // 1-hour decay
    }

    /**
     * Generate a random 6-digit code.
     */
    private function makeCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }
}
