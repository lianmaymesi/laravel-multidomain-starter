<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function portalUser(array $overrides = []): User
{
    return User::factory()->create(array_merge([
        'country_code' => '+91',
        'phone' => fake()->numerify('##########'),
        'privilege' => 'user',
    ], $overrides));
}

it('redirects authenticated end users away from the auth subdomain to the app portal', function () {
    $user = portalUser();

    $response = $this->actingAs($user)
        ->get(route('auth.login'));

    $response->assertRedirect(route('app.dashboard'));
});

it('redirects authenticated staff away from the auth subdomain to the backoffice portal', function () {
    $user = portalUser([
        'privilege' => 'staff',
    ]);

    $response = $this->actingAs($user)
        ->get(route('auth.login'));

    $response->assertRedirect(route('backoffice.dashboard'));
});

it('redirects staff away from the app portal to the backoffice portal', function () {
    $user = portalUser([
        'privilege' => 'staff',
        'phone_verified_at' => now(),
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('app.dashboard'));

    $response->assertRedirect(route('backoffice.dashboard'));
});

it('redirects end users away from the backoffice portal to the app portal', function () {
    $user = portalUser([
        'phone_verified_at' => now(),
        'email_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('backoffice.dashboard'));

    $response->assertRedirect(route('app.dashboard'));
});

it('allows both end users and staff to access the account portal', function () {
    $endUser = portalUser();

    $staffUser = portalUser([
        'privilege' => 'staff',
    ]);

    $this->actingAs($endUser)
        ->get(route('account.index'))
        ->assertSuccessful();

    auth()->logout();

    $this->actingAs($staffUser)
        ->get(route('account.index'))
        ->assertSuccessful();
});
