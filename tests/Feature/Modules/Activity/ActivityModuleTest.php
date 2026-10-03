<?php

use App\Models\Role;
use App\Modules\Activity\Models\Activity;
use App\Support\Modules\Module;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

uses(RefreshDatabase::class);

it('wires up logging, the page, nav and row buttons while the activity module is enabled', function () {
    $this->seed(RolePermissionSeeder::class);
    $this->withoutVite();

    expect(Module::enabled('activity'))->toBeTrue()
        ->and(Route::has('backoffice.activity.index'))->toBeTrue()
        ->and(config('activitylog.enabled'))->toBeTrue()
        ->and(collect(Module::contributions('backoffice.nav'))->pluck('route'))->toContain('backoffice.activity.index');

    Role::create(['name' => 'Editor', 'guard_name' => 'web']);
    expect(Activity::where('subject_type', Role::class)->exists())->toBeTrue();

    $actor = superAdminActor();

    $this->actingAs($actor)->get(route('backoffice.activity.index'))->assertOk();
    $this->actingAs($actor)->get(backofficeUrl('roles'))->assertOk()->assertSee('activity-role-');
    $this->actingAs($actor)->get(backofficeUrl('users'))->assertOk()->assertSee('activity-user-');
});

it('breaks nothing and stops logging when the activity module is disabled', function () {
    $this->disableModules('activity');
    $this->seed(RolePermissionSeeder::class);
    $this->withoutVite();

    expect(Module::enabled('activity'))->toBeFalse()
        ->and(Route::has('backoffice.activity.index'))->toBeFalse()
        ->and(config('activitylog.enabled'))->toBeFalse()
        // Schema and permissions survive so re-enabling needs no reseed.
        ->and(Schema::hasTable('activity_log'))->toBeTrue()
        ->and(Schema::hasTable('activity_comments'))->toBeTrue()
        ->and(Permission::where('name', 'activity.view')->exists())->toBeTrue();

    // Core models keep saving; nothing new is written to the log. (Baseline:
    // the migrations may already have logged a row before the toggle applied.)
    $baseline = Activity::count();

    $role = Role::create(['name' => 'Editor', 'guard_name' => 'web']);
    $role->update(['name' => 'Senior Editor']);
    $actor = superAdminActor();
    $actor->update(['name' => 'Renamed']);

    expect(Activity::count())->toBe($baseline);

    $this->actingAs($actor)->get(backofficeUrl('activity'))->assertNotFound();

    // Pages that used to show the per-row Activity button still render, without it.
    $this->actingAs($actor)->get(backofficeUrl('roles'))->assertOk()->assertDontSee('activity-role-');
    $this->actingAs($actor)->get(backofficeUrl('users'))->assertOk()->assertDontSee('activity-user-');
    $this->actingAs($actor)->get(route('backoffice.dashboard'))->assertOk()->assertDontSee('/activity');
});
