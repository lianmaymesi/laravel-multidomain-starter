<?php

namespace App\Modules\Language\Models;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\TranslationLoader\LanguageLine as SpatieLanguageLine;

class LanguageLine extends SpatieLanguageLine
{
    use LogsActivity;

    public const SCOPE_LANDING = 'landing';

    public const SCOPE_PORTAL = 'portal';

    public const SCOPE_COMMON = 'common';

    /**
     * @return array<int, string>
     */
    public static function scopes(): array
    {
        return [self::SCOPE_LANDING, self::SCOPE_PORTAL, self::SCOPE_COMMON];
    }

    /**
     * The permission that gates editing this line — 'common' is reserved
     * for Super Admin (see RolePermissionSeeder::ADMIN_EXCLUDED_PERMISSIONS),
     * 'landing' and 'portal' can be granted to Admin independently.
     */
    public function permission(): string
    {
        return "translations.{$this->scope}";
    }

    /**
     * `:token` placeholders (Laravel's __() substitution syntax) found in an
     * arbitrary string — used to check a translated value hasn't dropped one
     * a translator might not recognize as code rather than prose.
     *
     * @return array<int, string>
     */
    public static function extractPlaceholders(string $text): array
    {
        preg_match_all('/:[a-zA-Z_][a-zA-Z0-9_]*/', $text, $matches);

        return array_values(array_unique($matches[0]));
    }

    /**
     * @return array<int, string>
     */
    public function placeholders(): array
    {
        return self::extractPlaceholders($this->key);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['group', 'key', 'scope', 'text'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
