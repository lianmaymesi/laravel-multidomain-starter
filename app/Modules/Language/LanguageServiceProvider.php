<?php

namespace App\Modules\Language;

use App\Contracts\Languages;
use App\Models\AppSetting;
use App\Modules\Language\Http\Middleware\SetLocale;
use App\Modules\Language\Services\LanguageService;
use App\Support\Modules\Module;
use App\Support\Modules\ModuleProvider;
use App\Support\Settings\SettingField;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use Spatie\TranslationLoader\TranslationLoaders\Db;

class LanguageServiceProvider extends ModuleProvider
{
    protected function module(): string
    {
        return 'language';
    }

    protected function label(): string
    {
        return 'Languages';
    }

    protected function description(): string
    {
        return 'Multiple languages, translations and locale switching.';
    }

    protected function icon(): string
    {
        return 'language';
    }

    protected function permissions(): array
    {
        return [
            'languages.view',
            'languages.create',
            'languages.edit',
            'languages.delete',
            'translations.landing',
            'translations.portal',
            'translations.common',
        ];
    }

    protected function superAdminOnlyPermissions(): array
    {
        return [
            'languages.view',
            'languages.create',
            'languages.edit',
            'languages.delete',

            // "common" strings are reused across every portal including landing
            // — letting an Admin holding just one scope permission edit them
            // would leak into wording they weren't granted control over.
            'translations.common',
        ];
    }

    protected function registerModule(): void
    {
        // Core layouts, PortalResolver and route registration fall back to
        // NullLanguages without this.
        $this->app->bind(Languages::class, LanguageService::class);

        // spatie/laravel-translation-loader: database-stored translation
        // overrides only apply while this module is on (config default is []).
        config(['translation-loader.translation_loaders' => [Db::class]]);
    }

    protected function bootModule(): void
    {
        // Runs before other modules' web middleware (providers boot in folder
        // order) so the locale is already set when e.g. the maintenance page renders.
        Route::pushMiddlewareToGroup('web', SetLocale::class);

        Livewire::addNamespace('language', viewPath: $this->modulePath('resources/views/livewire'));
        Blade::anonymousComponentPath($this->modulePath('resources/views/components'), 'language');

        Module::contribute('backoffice.nav', [
            [
                'label' => 'Languages',
                'route' => 'backoffice.languages.index',
                'icon' => 'language',
                'permission' => 'languages.view',
                'order' => 30,
            ],
            [
                // Nothing to translate with a single active language, so this
                // stays hidden until there's a second one — same rule the
                // locale switcher itself follows.
                'label' => 'Translations',
                'route' => 'backoffice.translations.index',
                'icon' => 'chat-bubble-left-right',
                'permission' => ['translations.landing', 'translations.portal', 'translations.common'],
                'visible' => fn () => app(LanguageService::class)->isMultiLanguageEnabled(),
                'active' => 'backoffice.translations.*',
                'order' => 40,
            ],
        ]);

        // Built lazily (closures) — labels use __(), which must run after
        // SetLocale has picked the request's locale, not at boot.
        Module::contribute('settings.fields', [
            fn () => new SettingField(
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

            fn () => new SettingField(
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
}
