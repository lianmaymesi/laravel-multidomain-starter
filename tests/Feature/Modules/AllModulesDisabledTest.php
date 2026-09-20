<?php

use App\Models\User;
use App\Support\Modules\Module;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('keeps every portal working with all feature modules switched off', function () {
    $modules = Module::names();

    $this->disableModules(...$modules);
    $this->seed(RolePermissionSeeder::class);
    $this->withoutVite();

    foreach ($modules as $module) {
        expect(Module::enabled($module))->toBeFalse();
    }

    $this->get(route('index'))->assertOk();
    $this->get(route('auth.login'))->assertOk();

    $this->actingAs(superAdminActor())->get(route('backoffice.dashboard'))->assertOk();
    $this->actingAs(superAdminActor())->get(route('backoffice.settings.index'))->assertOk();

    $user = User::factory()->create(['privilege' => 'user', 'email_verified_at' => now(), 'phone_verified_at' => now()]);
    $this->actingAs($user)->get(route('account.settings'))->assertOk();
});
