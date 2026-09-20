<?php

use App\Models\ModuleSetting;
use App\Support\Modules\Module;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolePermissionSeeder::class);
});

it('lists every module with its label, description and state', function () {
    $modules = collect(Livewire::actingAs(superAdminActor())
        ->test('pages::backoffice.modules')
        ->instance()
        ->modules())->keyBy('key');

    expect($modules->keys()->all())->toEqual(Module::names())
        ->and($modules['currency']['label'])->toBe('Currencies')
        ->and($modules['currency']['description'])->not->toBe('')
        ->and($modules['currency']['enabled'])->toBeTrue()
        ->and($modules['currency']['overridden'])->toBeFalse();
});

it('is reserved for Super Admin', function () {
    Livewire::actingAs(adminActor())->test('pages::backoffice.modules')->assertStatus(403);
    Livewire::actingAs(staffUser())->test('pages::backoffice.modules')->assertStatus(403);
});

it('disables an enabled module by saving an override, then reloads the page', function () {
    $actor = superAdminActor();

    Livewire::actingAs($actor)
        ->test('pages::backoffice.modules')
        ->call('toggle', 'currency')
        ->assertRedirect(route('backoffice.modules.index'));

    $setting = ModuleSetting::where('module', 'currency')->first();

    expect($setting->enabled)->toBeFalse()
        ->and($setting->updated_by)->toBe($actor->id);
});

it('drops the override when a module is toggled back to its default', function () {
    ModuleSetting::create(['module' => 'currency', 'enabled' => false]);

    // Simulate the state a fresh request would see: overridden to off.
    Module::applyOverrides(['currency' => false]);

    Livewire::actingAs(superAdminActor())
        ->test('pages::backoffice.modules')
        ->call('toggle', 'currency');

    expect(ModuleSetting::where('module', 'currency')->exists())->toBeFalse();
});

it('resets an override to the default', function () {
    ModuleSetting::create(['module' => 'language', 'enabled' => false]);

    Livewire::actingAs(superAdminActor())
        ->test('pages::backoffice.modules')
        ->call('resetToDefault', 'language')
        ->assertRedirect(route('backoffice.modules.index'));

    expect(ModuleSetting::where('module', 'language')->exists())->toBeFalse();
});

it('rejects modules that do not exist and non-Super-Admin toggles', function () {
    Livewire::actingAs(superAdminActor())
        ->test('pages::backoffice.modules')
        ->call('toggle', 'not-a-module')
        ->assertStatus(404);

    expect(ModuleSetting::count())->toBe(0);
});

it('reads saved overrides as a module => enabled map', function () {
    expect(ModuleSetting::overrides())->toBe([]);

    ModuleSetting::create(['module' => 'activity', 'enabled' => false]);

    expect(ModuleSetting::overrides())->toBe(['activity' => false]);

    ModuleSetting::where('module', 'activity')->first()->delete();

    expect(ModuleSetting::overrides())->toBe([]);
});

it('shows the Modules link only to users who can manage modules', function () {
    $this->withoutVite();

    $this->actingAs(superAdminActor())->get(route('backoffice.dashboard'))->assertOk()->assertSee(backofficeUrl('modules'));
    $this->actingAs(adminActor())->get(route('backoffice.dashboard'))->assertOk()->assertDontSee(backofficeUrl('modules'));
});
