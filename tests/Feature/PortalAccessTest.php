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

it('redirects unverified-phone end users to phone verification when the feature is enabled', function () {
    config(['verification.phone_verification_enabled' => true]);

    $user = portalUser();

    $response = $this->actingAs($user)
        ->get(route('auth.login'));

    $response->assertRedirect(route('auth.verify-phone'));
});

it('redirects authenticated staff away from the auth subdomain to the backoffice portal', function () {
    $user = portalUser([
        'privilege' => 'staff',
        'phone_verified_at' => now(),
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

it('redirects phone verified end users away from the auth subdomain to the app portal', function () {
    $user = portalUser([
        'phone_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('auth.login'));

    $response->assertRedirect(route('app.dashboard'));
});

it('allows recently registered phone verified users to access the app portal before verifying email', function () {
    $user = portalUser([
        'email_verified_at' => null,
        'phone_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('app.dashboard'));

    $response->assertSuccessful();
});

it('redirects phone verified users to email verification after the grace period expires', function () {
    $user = portalUser([
        'created_at' => now()->subDays(config('verification.email_verification_grace_days') + 1),
        'email_verified_at' => null,
        'phone_verified_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('app.dashboard'));

    $response->assertRedirect(route('auth.verify-email'));
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
