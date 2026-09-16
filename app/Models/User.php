<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Concerns\Anonymizable;
use App\Notifications\VerifyEmail;
use App\Trait\MustVerifyPhone;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'country_code', 'phone', 'privilege', 'two_factor_secret', 'two_factor_recovery_codes', 'pending_email', 'pending_email_token'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Anonymizable, HasFactory, HasRoles, LogsActivity, MustVerifyPhone, Notifiable;

    /**
     * Only these are ever written to the activity log — password hash,
     * 2FA secret/recovery codes, and pending-email tokens must never end up
     * in activity_log.properties, logged or not.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'privilege'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_enabled_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'pending_email_requested_at' => 'datetime',
        ];
    }

    public function otps(): HasMany
    {
        return $this->hasMany(OtpCode::class);
    }

    public function deletionRequest(): HasOne
    {
        return $this->hasOne(AccountDeletionRequest::class);
    }

    public function dataExports(): HasMany
    {
        return $this->hasMany(AccountDataExport::class);
    }

    public function activeDeletionRequest(): ?AccountDeletionRequest
    {
        return $this->deletionRequest()
            ->whereIn('status', ['pending', 'processing'])
            ->first();
    }

    protected function anonymizeMap(): array
    {
        return [
            'name' => 'Deleted User',
            'email' => fn ($user) => "deleted_{$user->id}@deleted.invalid",
            'phone' => null,
            'country_code' => null,
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_enabled_at' => null,
            'two_factor_confirmed_at' => null,
            'pending_email' => null,
            'pending_email_token' => null,
            'pending_email_requested_at' => null,
            'remember_token' => null,
            'password' => fn () => Str::random(40),
        ];
    }

    /**
     * Show the user's phone number with country code.
     */
    public function fullPhone(): string
    {
        return $this->country_code.$this->phone;
    }

    public function emailVerificationExpired(): bool
    {
        if ($this->hasVerifiedEmail()) {
            return false;
        }

        return now()->isAfter($this->emailVerificationDeadline());
    }

    public function emailVerificationDeadlineDaysLeft(): int
    {
        return max(0, (int) now()->diffInDays($this->emailVerificationDeadline(), false));
    }

    private function emailVerificationDeadline(): Carbon
    {
        return $this->created_at->copy()->addDays(config('multidomain.email_verification_grace_days'));
    }

    public function hasPendingEmailChange(): bool
    {
        return ! is_null($this->pending_email) && ! is_null($this->pending_email_token);
    }

    public function pendingEmailExpired(): bool
    {
        if (! $this->pending_email_requested_at) {
            return true;
        }

        return $this->pending_email_requested_at->addHours(48)->isPast();
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_enabled_at
            && ! is_null($this->two_factor_confirmed_at);
    }

    public function twoFactorRecoveryCodes(): array
    {
        if (! $this->two_factor_recovery_codes) {
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

    /**
     * Portal identity lives in the role's `slug` (see App\Models\Role),
     * decoupled from the Title Case `name` shown in the UI. Spatie's own
     * hasRole() only matches `name`, so portal/system-role checks go
     * through this instead.
     */
    public function hasRoleSlug(string $slug): bool
    {
        return $this->roles->contains('slug', $slug);
    }

    /**
     * Send the user to their portal dashboard after login. A role sharing
     * its name with a configured subdomain (e.g. "blog", created via
     * `make:subdomain`) wins over the legacy app/backoffice privilege split.
     *
     * "app" and "backoffice" are excluded here on purpose: those two portals
     * are still gated by the isStaff()/portal:staff|user middleware below,
     * not by role. Letting a role of that name win here could send a
     * 'user'-privilege account to a route the middleware then bounces them
     * out of, bouncing back here — an infinite redirect loop.
     */
    public function redirect(): string
    {
        $portal = $this->roles->pluck('slug')
            ->reject(fn (string $role) => in_array($role, ['app', 'backoffice'], true))
            ->first(fn (string $role) => array_key_exists($role, config('multidomain.sub_domains', [])) && Route::has("{$role}.dashboard"));

        if ($portal) {
            return route("{$portal}.dashboard");
        }

        return $this->isStaff()
            ? route('backoffice.dashboard')
            : route('app.dashboard');
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmail);
    }
}
