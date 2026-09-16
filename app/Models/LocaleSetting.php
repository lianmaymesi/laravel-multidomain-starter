<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LocaleSetting extends Model
{
    use LogsActivity;

    public const MODE_QUERY = 'query';

    public const MODE_PATH = 'path';

    public const CACHE_KEY = 'locale-settings.url-mode';

    protected $fillable = [
        'url_mode',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['url_mode'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * Single-row settings table — the migration seeds row #1, so this is
     * only a fallback for a database restored without it.
     */
    public static function current(): self
    {
        return static::query()->first() ?? static::create(['url_mode' => self::MODE_PATH]);
    }

    public static function urlMode(): string
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::current()->url_mode);
    }

    public static function isPathMode(): bool
    {
        return self::urlMode() === self::MODE_PATH;
    }

    public static function isQueryMode(): bool
    {
        return self::urlMode() === self::MODE_QUERY;
    }
}
