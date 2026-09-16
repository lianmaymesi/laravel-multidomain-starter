<?php

namespace App\Models;

use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use LogsActivity;

    public const SUPER_ADMIN = 'super-admin';

    public const ADMIN = 'admin';

    protected static function booted(): void
    {
        static::creating(function (self $role) {
            if (! $role->slug) {
                $role->slug = Str::slug($role->name);
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'slug', 'locked'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * A portal role's slug matches a configured subdomain key (e.g. "blog",
     * scaffolded via `make:subdomain`) and drives the `portal:{slug}`
     * middleware and post-login redirect — see EnsurePortalAccess and
     * User::redirect().
     */
    public function isPortalRole(): bool
    {
        return array_key_exists($this->slug, config('multidomain.sub_domains', []));
    }
}
