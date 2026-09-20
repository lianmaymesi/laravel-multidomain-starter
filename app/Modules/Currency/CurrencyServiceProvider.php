<?php

namespace App\Modules\Currency;

use App\Contracts\Currencies;
use App\Modules\Currency\Jobs\RefreshExchangeRates;
use App\Modules\Currency\Seeders\CurrencySeeder;
use App\Modules\Currency\Services\CurrencyService;
use App\Support\Modules\Module;
use App\Support\Modules\ModuleProvider;
use Illuminate\Console\Scheduling\Schedule;
use Livewire\Livewire;

class CurrencyServiceProvider extends ModuleProvider
{
    protected function module(): string
    {
        return 'currency';
    }

    protected function label(): string
    {
        return 'Currencies';
    }

    protected function description(): string
    {
        return 'Currencies, exchange rates and money formatting.';
    }

    protected function icon(): string
    {
        return 'banknotes';
    }

    protected function permissions(): array
    {
        return ['currencies.view'];
    }

    protected function seeders(): array
    {
        return [CurrencySeeder::class];
    }

    protected function registerModule(): void
    {
        // Core's Money/MoneyCast fall back to NullCurrencies without this.
        $this->app->bind(Currencies::class, CurrencyService::class);
    }

    protected function bootModule(): void
    {
        Livewire::addNamespace('currency', viewPath: $this->modulePath('resources/views/livewire'));

        Module::contribute('backoffice.nav', [[
            'label' => 'Currencies',
            'route' => 'backoffice.currencies.index',
            'icon' => 'banknotes',
            'permission' => 'currencies.view',
            'order' => 20,
        ]]);

        // Managing which currencies are active/primary lives on the Settings page.
        Module::contribute('backoffice.settings.cards', [[
            'component' => 'currency::settings-card',
            'permission' => 'settings.edit',
            'order' => 20,
        ]]);
    }

    protected function schedule(Schedule $schedule): void
    {
        // Refresh currency exchange rates — runs daily at 01:00
        $schedule->job(new RefreshExchangeRates)->dailyAt('01:00')->onOneServer();
    }
}
