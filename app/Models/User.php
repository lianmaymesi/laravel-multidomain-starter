<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\VerifyEmail;
use App\Trait\MustVerifyPhone;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'country_code', 'phone', 'privilege', 'two_factor_secret', 'two_factor_recovery_codes'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, MustVerifyPhone, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_enabled_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Get all of the otps for the User
     */
    public function otps(): HasMany
    {
        return $this->hasMany(OtpCode::class);
    }

    /**
     * Show the user's phone number with country code.
     */
    public function fullPhone(): string
    {
        return $this->country_code . $this->phone;
    }

    public function emailVerificationExpired(): bool
    {
        if ($this->hasVerifiedEmail()) {
            return false;
        }

        return now()->isAfter($this->created_at);
    }

    public function emailVerificationDeadlineDaysLeft(): int
    {
        return max(0, (int) now()->diffInDays($this->created_at, false));
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_enabled_at
            && !is_null($this->two_factor_confirmed_at);
    }

    public function twoFactorRecoveryCodes(): array
    {
        if (!$this->two_factor_recovery_codes) {
            return [];
        }

        return json_decode(
            decrypt($this->two_factor_recovery_codes),
            true
        ) ?? [];
    }

    public function isStaff(): bool
    {
        return $this->privilege === 'staff';
    }

    public function redirect(): string
    {
        return $this->isStaff()
            ? route('backoffice.dashboard')
            : route('app.dashboard');
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmail);
    }
}
