<?php

namespace App\Services;

use App\Models\AppSetting;
use App\Support\Settings\SettingField;
use Illuminate\Support\Collection;

/**
 * Every app-level setting the backoffice Settings page can edit, declared
 * once here. The page itself (`resources/views/pages/backoffice/⚡settings`)
 * just loops over `all()` and renders whichever widget each field's `type`
 * calls for — add a setting by adding an entry here, not by editing the
 * Blade/PHP files.
 */
class SettingsRegistry
{
    /**
     * @return Collection<int, SettingField>
     */
    public function all(): Collection
    {
        return collect([
            new SettingField(
                key: AppSetting::URL_MODE,
                type: SettingField::TYPE_RADIO,
                label: __('Language URL mode'),
                description: __("Only one of these is ever active — switching here changes how every portal's URLs are generated."),
                permission: 'languages.edit',
                default: AppSetting::MODE_PATH,
                rules: ['required', 'in:'.AppSetting::MODE_PATH.','.AppSetting::MODE_QUERY],
                options: [
                    AppSetting::MODE_PATH => ['label' => __('Path prefix'), 'description' => 'example.com/ · example.com/ar · example.com/ta'],
                    AppSetting::MODE_QUERY => ['label' => __('Query string'), 'description' => 'example.com/?lang=ar'],
                ],
            ),

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

            new SettingField(
                key: AppSetting::GOOGLE_TRANSLATE_API_KEY,
                type: SettingField::TYPE_SECRET,
                label: __('Google Translate API key'),
                description: __('Lets admins machine-translate pending strings on the Translations page with one click. Optional — leave blank to keep translating by hand.'),
                permission: 'settings.edit',
                helpText: __('Google Cloud Console'),
                helpUrl: 'https://console.cloud.google.com/apis/credentials',
            ),
        ]);
    }

    public function find(string $key): ?SettingField
    {
        return $this->all()->firstWhere('key', $key);
    }
}
