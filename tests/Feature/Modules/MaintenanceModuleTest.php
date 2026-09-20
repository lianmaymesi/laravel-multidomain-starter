<?php

use App\Modules\Maintenance\Http\Middleware\CheckMaintenance;
use App\Modules\Maintenance\Models\PortalSetting;
use App\Support\Modules\Module;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('wires up routes, middleware and config when the maintenance module is enabled', function () {
    expect(Module::enabled('maintenance'))->toBeTrue()
        ->and(Route::has('backoffice.maintenance.index'))->toBeTrue()
        ->and(Route::getMiddlewareGroups()['web'])->toContain(CheckMaintenance::class)
        ->and(config('maintenance.exempt_portals'))->toContain('backoffice');
});

it('takes a portal down through the middleware while enabled', function () {
    PortalSetting::create(['portal' => 'landing', 'maintenance_mode' => true]);

    $this->get(route('index'))->assertStatus(503);
});

it('breaks nothing when the maintenance module is disabled', function () {
    $this->disableModules('maintenance');
    $this->seed(RolePermissionSeeder::class);
    $this->withoutVite();

    expect(Module::enabled('maintenance'))->toBeFalse()
        ->and(Route::has('backoffice.maintenance.index'))->toBeFalse()
        ->and(Route::getMiddlewareGroups()['web'])->not->toContain(CheckMaintenance::class)
        ->and(config('maintenance'))->toBeNull()
        // Data stays put — the schema is still migrated so re-enabling needs nothing.
        ->and(Schema::hasTable('portal_settings'))->toBeTrue();

    // A row saying "down" is ignored: nothing consults it any more.
    PortalSetting::create(['portal' => 'landing', 'maintenance_mode' => true]);
    $this->get(route('index'))->assertOk();

    $actor = superAdminActor();

    // Its old URL 404s cleanly (route never registered), no 500.
    $this->actingAs($actor)->get(backofficeUrl('maintenance'))->assertNotFound();

    // Backoffice still renders, and the nav entry is gone.
    $this->actingAs($actor)->get(route('backoffice.dashboard'))
        ->assertOk()
        ->assertDontSee('/maintenance');
});
