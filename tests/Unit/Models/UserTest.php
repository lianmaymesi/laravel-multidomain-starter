<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('concatenates the country code and phone number', function () {
    $user = User::factory()->create(['country_code' => '+91', 'phone' => '9876543210']);

    expect($user->fullPhone())->toBe('+919876543210');
});

it('reports staff privilege correctly', function () {
    expect(User::factory()->make(['privilege' => 'staff'])->isStaff())->toBeTrue();
    expect(User::factory()->make(['privilege' => 'user'])->isStaff())->toBeFalse();
});

it('is not expired for email verification within the grace period', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'created_at' => now(),
    ]);

    expect($user->emailVerificationExpired())->toBeFalse();
    expect($user->emailVerificationDeadlineDaysLeft())->toBeGreaterThanOrEqual(config('multidomain.email_verification_grace_days') - 1);
});

it('is expired for email verification once the grace period has passed', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
        'created_at' => now()->subDays(config('multidomain.email_verification_grace_days') + 1),
    ]);

    expect($user->emailVerificationExpired())->toBeTrue();
    expect($user->emailVerificationDeadlineDaysLeft())->toBe(0);
});

it('is never expired once the email is verified', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'created_at' => now()->subDays(365),
    ]);

    expect($user->emailVerificationExpired())->toBeFalse();
});

it('detects a pending email change', function () {
    $user = User::factory()->make(['pending_email' => null, 'pending_email_token' => null]);
    expect($user->hasPendingEmailChange())->toBeFalse();

    $user = User::factory()->make(['pending_email' => 'new@example.com', 'pending_email_token' => 'token']);
    expect($user->hasPendingEmailChange())->toBeTrue();
});

it('expires a pending email change after 48 hours', function () {
    $recent = User::factory()->make(['pending_email_requested_at' => now()->subHours(47)]);
    expect($recent->pendingEmailExpired())->toBeFalse();

    $stale = User::factory()->make(['pending_email_requested_at' => now()->subHours(49)]);
    expect($stale->pendingEmailExpired())->toBeTrue();

    $none = User::factory()->make(['pending_email_requested_at' => null]);
    expect($none->pendingEmailExpired())->toBeTrue();
});

it('only reports two-factor enabled once confirmed', function () {
    $notEnabled = User::factory()->make(['two_factor_enabled_at' => null, 'two_factor_confirmed_at' => null]);
    expect($notEnabled->hasTwoFactorEnabled())->toBeFalse();

    $enabledNotConfirmed = User::factory()->make(['two_factor_enabled_at' => now(), 'two_factor_confirmed_at' => null]);
    expect($enabledNotConfirmed->hasTwoFactorEnabled())->toBeFalse();

    $fullyEnabled = User::factory()->make(['two_factor_enabled_at' => now(), 'two_factor_confirmed_at' => now()]);
    expect($fullyEnabled->hasTwoFactorEnabled())->toBeTrue();
});

it('returns empty recovery codes when none are set', function () {
    $user = User::factory()->make(['two_factor_recovery_codes' => null]);

    expect($user->twoFactorRecoveryCodes())->toBe([]);
});

it('redirects end users to the app dashboard and staff to the backoffice dashboard', function () {
    $endUser = User::factory()->create(['privilege' => 'user']);
    $staff = User::factory()->create(['privilege' => 'staff']);

    expect($endUser->redirect())->toBe(route('app.dashboard'));
    expect($staff->redirect())->toBe(route('backoffice.dashboard'));
});

it('redirects a user with a matching subdomain role to that portal instead', function () {
    config(['multidomain.sub_domains.blog' => 'blog.test']);
    Role::create(['name' => 'blog', 'guard_name' => 'web']);

    Route::get('/test-blog-dashboard', fn () => 'ok')->name('blog.dashboard');
    Route::getRoutes()->refreshNameLookups();

    $user = User::factory()->create(['privilege' => 'user']);
    $user->assignRole('blog');

    expect($user->redirect())->toBe(route('blog.dashboard'));
});

it('ignores app and backoffice roles when computing the redirect to avoid loops', function () {
    Role::create(['name' => 'backoffice', 'guard_name' => 'web']);

    $user = User::factory()->create(['privilege' => 'user']);
    $user->assignRole('backoffice');

    // Without the app/backoffice exclusion this would resolve to the
    // backoffice dashboard and immediately bounce a "user"-privilege
    // account back out via the portal:staff middleware — an infinite loop.
    expect($user->redirect())->toBe(route('app.dashboard'));
});

it('anonymizes a user, replacing sensitive columns', function () {
    $user = User::factory()->create([
        'name' => 'Real Name',
        'email' => 'real@example.com',
        'country_code' => '+91',
        'phone' => '9876543210',
    ]);

    $user->anonymize();
    $user->refresh();

    expect($user->name)->toBe('Deleted User')
        ->and($user->email)->toBe("deleted_{$user->id}@deleted.invalid")
        ->and($user->phone)->toBeNull()
        ->and($user->country_code)->toBeNull();
});
