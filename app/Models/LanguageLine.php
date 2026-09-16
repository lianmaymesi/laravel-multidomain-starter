<?php

namespace App\Models;

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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['group', 'key', 'scope', 'text'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
