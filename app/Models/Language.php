<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Language extends Model
{
    use LogsActivity;

    public const CACHE_KEY = 'languages.active';

    protected $fillable = [
        'code',
        'name',
        'native_name',
        'direction',
        'is_primary',
        'is_active',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['code', 'name', 'native_name', 'direction', 'is_primary', 'is_active', 'order'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Only one language may be primary — making one primary demotes
     * whichever row currently holds it, so callers never have to do
     * that bookkeeping themselves.
     */
    protected static function booted(): void
    {
        static::saving(function (self $language) {
            if ($language->is_primary) {
                static::where('id', '!=', $language->id)->update(['is_primary' => false]);
            }
        });

        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Cached as plain attribute arrays, not hydrated models — the database
     * cache store has `serializable_classes` disabled (see config/cache.php),
     * so any cached object silently comes back as __PHP_Incomplete_Class.
     * Rehydrating via `hydrate()` keeps casts working after the cache hit.
     *
     * @return Collection<int, self>
     */
    public static function active(): Collection
    {
        $rows = Cache::rememberForever(
            self::CACHE_KEY,
            fn () => static::where('is_active', true)->orderBy('order')->get()->map->getAttributes()->all(),
        );

        return static::hydrate($rows);
    }

    public static function primary(): ?self
    {
        return static::active()->firstWhere('is_primary', true) ?? static::active()->first();
    }
}
