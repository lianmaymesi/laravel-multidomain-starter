<?php

use App\Models\ModuleSetting;
use App\Support\Modules\Module;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

/*
 * These read saved overrides while the application boots, so they run against
 * a throwaway SQLite file (see TestCase::useSqliteFile) and reboot the app
 * between writing a row and checking its effect. No RefreshDatabase here.
 */

it('boots on the config defaults when the module_settings table does not exist yet', function () {
    $this->useSqliteFile();
    $this->refreshApplication();

    expect(Module::enabled('currency'))->toBeTrue()
        ->and(Module::isOverridden('currency'))->toBeFalse();
});

it('switches a module off at boot when a saved override says so', function () {
    $this->useSqliteFile();
    $this->refreshApplication();
    $this->artisan('migrate')->assertSuccessful();

    ModuleSetting::create(['module' => 'currency', 'enabled' => false]);

    $this->refreshApplication();

    expect(Module::enabled('currency'))->toBeFalse()
        ->and(Route::has('backoffice.currencies.index'))->toBeFalse()
        ->and(Module::isOverridden('currency'))->toBeTrue()
        ->and(Module::defaultEnabled('currency'))->toBeTrue()
        ->and(Module::info('currency')['label'])->toBe('Currencies')
        // Other modules are untouched.
        ->and(Module::enabled('language'))->toBeTrue();
});

it('lets a saved override switch on a module the config default has off', function () {
    $this->useSqliteFile();
    $this->disableModules('currency'); // MODULE_CURRENCY=false in the environment
    $this->artisan('migrate')->assertSuccessful();

    expect(Module::enabled('currency'))->toBeFalse();

    ModuleSetting::create(['module' => 'currency', 'enabled' => true]);

    $this->refreshApplication();

    expect(Module::enabled('currency'))->toBeTrue()
        ->and(Module::defaultEnabled('currency'))->toBeFalse()
        ->and(Module::isOverridden('currency'))->toBeTrue()
        ->and(Route::has('backoffice.currencies.index'))->toBeTrue();
});

it('ignores overrides for modules that no longer exist', function () {
    $this->useSqliteFile();
    $this->refreshApplication();
    $this->artisan('migrate')->assertSuccessful();

    ModuleSetting::create(['module' => 'removed-module', 'enabled' => true]);

    $this->refreshApplication();

    expect(Module::names())->not->toContain('removed-module')
        ->and(Module::enabled('removed-module'))->toBeFalse();
});

it('disables a module from the Modules page and enables it again, end to end', function () {
    $this->useSqliteFile();
    $this->refreshApplication();
    $this->artisan('migrate')->assertSuccessful();
    $this->seed(RolePermissionSeeder::class);

    $actor = superAdminActor();

    expect(Route::has('backoffice.currencies.index'))->toBeTrue();

    // Disable via the page...
    Livewire::actingAs($actor)->test('pages::backoffice.modules')->call('toggle', 'currency');

    // ...and the next request (a fresh boot) no longer has the module.
    $this->refreshApplication();

    expect(Module::enabled('currency'))->toBeFalse()
        ->and(Route::has('backoffice.currencies.index'))->toBeFalse()
        ->and(Module::info('currency')['overridden'])->toBeTrue();

    // Enable it again from the page.
    Livewire::actingAs($actor)->test('pages::backoffice.modules')->call('toggle', 'currency');

    $this->refreshApplication();

    expect(Module::enabled('currency'))->toBeTrue()
        ->and(Route::has('backoffice.currencies.index'))->toBeTrue()
        // Toggled back to the default, so no override row is left over.
        ->and(Module::info('currency')['overridden'])->toBeFalse();
});
