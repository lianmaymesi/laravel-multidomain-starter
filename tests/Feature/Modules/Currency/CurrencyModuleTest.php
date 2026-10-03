<?php

use App\Contracts\Currencies;
use App\Modules\Currency\Jobs\RefreshExchangeRates;
use App\Modules\Currency\Seeders\CurrencySeeder;
use App\Modules\Currency\Services\CurrencyService;
use App\Support\Modules\Module;
use App\Support\Money;
use App\Support\NullCurrencies;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

function scheduledCurrencyRefresh(): bool
{
    return collect(app(Schedule::class)->events())
        ->contains(fn ($event) => str_contains((string) $event->description, RefreshExchangeRates::class));
}

it('wires up everything while the currency module is enabled', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->seed(CurrencySeeder::class);
    $this->withoutVite();

    expect(Module::enabled('currency'))->toBeTrue()
        ->and(Route::has('backoffice.currencies.index'))->toBeTrue()
        ->and(app(Currencies::class))->toBeInstanceOf(CurrencyService::class)
        ->and(scheduledCurrencyRefresh())->toBeTrue()
        ->and(collect(Module::contributions('backoffice.nav'))->pluck('route'))->toContain('backoffice.currencies.index');

    $this->actingAs(superAdminActor())
        ->get(route('backoffice.settings.index'))
        ->assertOk()
        ->assertSee('Save currencies');

    expect((new Money(1250, 'USD'))->format())->toBe('$12.50');
});

it('breaks nothing when the currency module is disabled', function () {
    $this->disableModules('currency');
    $this->seed(RolePermissionSeeder::class);
    $this->withoutVite();

    expect(Module::enabled('currency'))->toBeFalse()
        ->and(Route::has('backoffice.currencies.index'))->toBeFalse()
        ->and(scheduledCurrencyRefresh())->toBeFalse()
        // Core money helpers keep working through the null-object fallback.
        ->and(app(Currencies::class))->toBeInstanceOf(NullCurrencies::class)
        ->and((new Money(1250, 'USD'))->format())->toBe('USD 12.50')
        ->and((new Money(1250, 'USD'))->convertTo('EUR')->minorUnits)->toBe(1250)
        // Schema and permission survive so re-enabling needs no reseed.
        ->and(Schema::hasTable('currencies'))->toBeTrue()
        ->and(Permission::where('name', 'currencies.view')->exists())->toBeTrue();

    $actor = superAdminActor();

    $this->actingAs($actor)->get(backofficeUrl('currencies'))->assertNotFound();

    // Backoffice and Settings still render, without the currency nav link or card.
    $this->actingAs($actor)->get(route('backoffice.dashboard'))
        ->assertOk()
        ->assertDontSee('/currencies');

    $this->actingAs($actor)->get(route('backoffice.settings.index'))
        ->assertOk()
        ->assertDontSee('Save currencies');
});
