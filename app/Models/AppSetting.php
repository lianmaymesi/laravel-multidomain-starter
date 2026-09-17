<?php

namespace App\Models;

use App\Support\Settings\SettingField;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AppSetting extends Model
{
    use LogsActivity;

    public const CACHE_KEY = 'app-settings.all';

    public const DEFAULT_TIMEZONE = 'default_timezone';

    public const URL_MODE = 'url_mode';

    public const MODE_PATH = 'path';

    public const MODE_QUERY = 'query';

    public const GOOGLE_TRANSLATE_API_KEY = 'google_translate_api_key';

    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Values are never logged — some settings (API keys) are secrets, and
     * this table has no way to tell a secret key from an ordinary one at
     * the activity-log layer, so no value is ever written to the audit
     * trail here. Only which key changed is recorded.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['key'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * @return array<string, ?string>
     */
    private static function cached(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => static::query()->pluck('value', 'key')->all());
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::cached()[$key] ?? $default;
    }

    public static function set(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function defaultTimezone(): string
    {
        return self::get(self::DEFAULT_TIMEZONE, config('app.timezone'));
    }

    public static function urlMode(): string
    {
        return self::get(self::URL_MODE, self::MODE_PATH);
    }

    public static function isPathMode(): bool
    {
        return self::urlMode() === self::MODE_PATH;
    }

    public static function isQueryMode(): bool
    {
        return self::urlMode() === self::MODE_QUERY;
    }

    /**
     * Some settings need one value per active language (e.g. a maintenance
     * banner or welcome message) rather than a single scalar — stored the
     * same shape as LanguageLine::text: JSON keyed by locale code.
     *
     * @param  array<string, string>  $translations  locale code => text
     */
    public static function setTranslated(string $key, array $translations): void
    {
        self::set($key, json_encode($translations));
    }

    public static function getTranslated(string $key, ?string $locale = null, mixed $default = null): mixed
    {
        $raw = self::get($key);

        if ($raw === null) {
            return $default;
        }

        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return $default;
        }

        $locale ??= app()->getLocale();

        return $decoded[$locale] ?? $decoded[config('app.locale')] ?? $default;
    }

    /**
     * For secrets (API keys) — encrypted at rest with the app key, so a
     * database dump/backup alone never exposes them. Never echo the
     * decrypted value back into a page; only ever show whether one is set.
     */
    public static function setEncrypted(string $key, ?string $value): void
    {
        self::set($key, $value === null || $value === '' ? null : Crypt::encryptString($value));
    }

    public static function getDecrypted(string $key): ?string
    {
        $raw = self::get($key);

        if ($raw === null) {
            return null;
        }

        try {
            return Crypt::decryptString($raw);
        } catch (DecryptException) {
            return null;
        }
    }

    public static function hasEncrypted(string $key): bool
    {
        return self::getDecrypted($key) !== null;
    }

    /**
     * Generic read for any `SettingField` from `App\Services\SettingsRegistry`
     * — decodes according to the field's declared type so the Settings page
     * never has to know how a particular setting is encoded on disk. A
     * secret's value is never returned here; check `hasEncrypted()` instead.
     */
    public static function value(SettingField $field): mixed
    {
        return match ($field->type) {
            SettingField::TYPE_SECRET => null,
            SettingField::TYPE_JSON => json_decode(self::get($field->key) ?? '', true) ?? $field->default,
            SettingField::TYPE_BOOLEAN => filter_var(self::get($field->key, $field->default ? '1' : '0'), FILTER_VALIDATE_BOOLEAN),
            default => self::get($field->key, $field->default),
        };
    }

    /**
     * Generic write for any `SettingField` — encodes according to the
     * field's declared type. For a secret, passing an empty/null value is a
     * no-op (the Settings page uses this to mean "leave the current key
     * alone"; use `setEncrypted($key, null)` directly to actually clear one).
     */
    public static function saveField(SettingField $field, mixed $input): void
    {
        match ($field->type) {
            SettingField::TYPE_SECRET => ($input !== null && $input !== '') ? self::setEncrypted($field->key, $input) : null,
            SettingField::TYPE_JSON => self::set($field->key, json_encode($input)),
            SettingField::TYPE_BOOLEAN => self::set($field->key, $input ? '1' : '0'),
            default => self::set($field->key, $input === null ? null : (string) $input),
        };
    }
}
