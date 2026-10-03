<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Throwable;

/**
 * A runtime override of one feature toggle from config/modules.php, edited on
 * the backoffice Modules page. Only differences from the config default are
 * stored — see App\Support\Modules\ModuleManager::applyOverrides().
 */
class ModuleSetting extends Model
{
    use LogsActivity;

    protected $fillable = [
        'module',
        'enabled',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['module', 'enabled'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Read while the app boots — before any module registers — so it must
     * never throw: on a fresh install (or during `migrate`) the table may not
     * exist yet, and the config defaults simply apply.
     *
     * Deliberately neither Eloquent nor the cache: at provider-register time
     * Eloquent's connection resolver isn't set yet (the database provider sets
     * it in boot) and the cache service is still deferred. One indexed
     * read of a handful of rows per request is cheap.
     *
     * @return array<string, bool> module => enabled
     */
    public static function overrides(): array
    {
        try {
            return DB::table((new static)->getTable())
                ->pluck('enabled', 'module')
                ->map(fn ($enabled) => (bool) $enabled)
                ->all();
        } catch (Throwable) {
            return [];
        }
    }
}
