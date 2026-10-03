<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Support\Modules\Module;
use App\Support\Settings\SettingField;
use Closure;
use Illuminate\Support\Collection;

/**
 * Every app-level setting the backoffice Settings page can edit: the core
 * ones declared here, plus whatever enabled modules contribute through
 * Module::contribute('settings.fields', [...]). The page itself (`resources/views/pages/backoffice/⚡settings`)
 * just loops over `all()` and renders whichever widget each field's `type`
 * calls for — add a setting by adding an entry here (or from a module), not by
 * editing the Blade/PHP files.
 */
class SettingsRegistry
{
    /**
     * @return Collection<int, SettingField>
     */
    public function all(): Collection
    {
        $core = [
            new SettingField(
                key: AppSetting::DEFAULT_TIMEZONE,
                type: SettingField::TYPE_SELECT,
                label: __('Default timezone'),
                description: __("Used to display dates for any user who hasn't set their own timezone."),
                permission: 'settings.edit',
                default: config('app.timezone'),
                rules: ['required', 'timezone'],
                options: collect(app(TimezoneService::class)->identifiers())->mapWithKeys(fn (string $tz) => [$tz => $tz])->all(),
            ),
        ];

        // Feature modules add their own settings via the 'settings.fields'
        // extension point — closures, so labels are translated per request.
        $fromModules = array_map(
            fn ($field) => $field instanceof Closure ? $field() : $field,
            Module::contributions('settings.fields'),
        );

        return collect([...$core, ...$fromModules]);
    }

    public function find(string $key): ?SettingField
    {
        return $this->all()->firstWhere('key', $key);
    }
}
