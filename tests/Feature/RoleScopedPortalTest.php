<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['multidomain.sub_domains.blog' => 'blog.test']);

    Role::create(['name' => 'blog', 'guard_name' => 'web']);

    Route::get('/test-blog-dashboard', fn () => 'ok')
        ->middleware(['auth', 'portal:blog'])
        ->name('blog.dashboard');

    // Named routes added after boot aren't in the name lookup index until refreshed.
    Route::getRoutes()->refreshNameLookups();
});

function roleScopedPortalUser(array $overrides = []): User
{
    return User::factory()->create(array_merge([
        'country_code' => '+91',
        'phone' => fake()->numerify('##########'),
        'privilege' => 'user',
        'phone_verified_at' => now(),
        'email_verified_at' => now(),
    ], $overrides));
}

it('lets a user with the matching role into a role-scoped portal', function () {
    $user = roleScopedPortalUser();
    $user->assignRole('blog');

    $this->actingAs($user)
        ->get(route('blog.dashboard'))
        ->assertSuccessful();
});

it('bounces a user without the matching role away from a role-scoped portal', function () {
    $user = roleScopedPortalUser();

    $this->actingAs($user)
        ->get(route('blog.dashboard'))
        ->assertRedirect($user->redirect());
});

it('sends a user with a subdomain role to that portal after login', function () {
    $user = roleScopedPortalUser();
    $user->assignRole('blog');

    expect($user->redirect())->toBe(route('blog.dashboard'));
});
