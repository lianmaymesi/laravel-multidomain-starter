<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PortalSetting extends Model
{
    use LogsActivity;

    protected $fillable = [
        'portal',
        'maintenance_mode',
        'message',
        'updated_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['maintenance_mode', 'message'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected function casts(): array
    {
        return [
            'maintenance_mode' => 'boolean',
        ];
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public static function isDown(string $portal): bool
    {
        return (bool) (static::cached($portal)['maintenance_mode'] ?? false);
    }

    public static function messageFor(string $portal): ?string
    {
        return static::cached($portal)['message'] ?? null;
    }

    /**
     * Cached as a plain array rather than the Eloquent model — cheaper to
     * serialize through the cache store and avoids any hydration surprises
     * for what's ultimately just two scalar values.
     *
     * @return array{maintenance_mode: bool, message: ?string}|null
     */
    protected static function cached(string $portal): ?array
    {
        return Cache::remember(
            "portal-setting:{$portal}",
            now()->addMinutes(5),
            function () use ($portal) {
                $setting = static::where('portal', $portal)->first();

                return $setting ? [
                    'maintenance_mode' => $setting->maintenance_mode,
                    'message' => $setting->message,
                ] : null;
            },
        );
    }

    protected static function booted(): void
    {
        static::saved(fn (self $setting) => Cache::forget("portal-setting:{$setting->portal}"));
        static::deleted(fn (self $setting) => Cache::forget("portal-setting:{$setting->portal}"));
    }
}
