<?php

use App\Models\User;
use App\Support\Modules\Module;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('is off by default — no API, no token pages, no nav', function () {
    $this->seed(RolePermissionSeeder::class);

    expect(Module::enabled('api'))->toBeFalse()
        ->and(Route::has('api.v1.me'))->toBeFalse()
        ->and(Route::has('api.v1.languages.index'))->toBeFalse()
        ->and(Route::has('account.api-tokens'))->toBeFalse()
        ->and(Route::has('backoffice.api.index'))->toBeFalse();

    $this->get('http://'.config('multidomain.sub_domains.api').'/v1/me')->assertNotFound();

    $this->actingAs(superAdminActor())->get(route('backoffice.dashboard'))
        ->assertOk()
        ->assertDontSee('API Access');
});

it('is listed on the Modules page so a Super Admin can switch it on', function () {
    expect(Module::names())->toContain('api')
        ->and(Module::info('api')['label'])->toBe('API');
});

it('switching it on brings the API and the backoffice page', function () {
    $this->enableModules('api');
    $this->seed(RolePermissionSeeder::class);

    expect(Route::has('api.v1.me'))->toBeTrue()
        ->and(Route::has('backoffice.api.index'))->toBeTrue();

    $this->getJson(route('api.v1.index'))->assertOk();
});

it('keeps tokens in the database while off', function () {
    $user = User::factory()->create();
    $user->createToken('kept', ['profile:read']);

    expect($user->tokens()->count())->toBe(1);
});
