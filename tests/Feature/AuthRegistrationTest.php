<?php

use App\Contracts\SmsService;
use App\Enums\OtpType;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('registers a user and redirects them to phone verification', function () {
    config(['multidomain.phone_verification_enabled' => true]);

    $smsService = Mockery::mock(SmsService::class);
    $smsService->shouldReceive('sendOtp')
        ->once()
        ->with('+919876543210', Mockery::pattern('/^\d{6}$/'))
        ->andReturnTrue();

    $this->app->instance(SmsService::class, $smsService);

    Livewire::test('pages::auth.register')
        ->set('name', 'Taylor Otwell')
        ->set('email', 'taylor@example.com')
        ->set('password', 'Jrb!2026-Register-Flow-Q9v#72')
        ->set('password_confirmation', 'Jrb!2026-Register-Flow-Q9v#72')
        ->set('country_code', '+91')
        ->set('phone', '9876543210')
        ->call('register')
        ->assertRedirect(route('auth.verify-phone'));

    $user = User::where('email', 'taylor@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->hasVerifiedPhone())->toBeFalse();

    $this->assertAuthenticatedAs($user);

    $this->assertDatabaseHas('otp_codes', [
        'user_id' => $user->id,
        'type' => OtpType::PHONE_VERIFICATION->value,
        'used_at' => null,
    ]);
});

it('registers a user without a phone number when phone verification is disabled', function () {
    config(['verification.phone_verification_enabled' => false]);

    $smsService = Mockery::mock(SmsService::class);
    $smsService->shouldNotReceive('sendOtp');

    $this->app->instance(SmsService::class, $smsService);

    Livewire::test('pages::auth.register')
        ->set('name', 'Taylor Otwell')
        ->set('email', 'taylor@example.com')
        ->set('password', 'Jrb!2026-Register-Flow-Q9v#72')
        ->set('password_confirmation', 'Jrb!2026-Register-Flow-Q9v#72')
        ->call('register')
        ->assertRedirect(route('app.dashboard'));

    $user = User::where('email', 'taylor@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->phone)->toBeNull()
        ->and($user->country_code)->toBeNull()
        ->and($user->hasVerifiedPhone())->toBeTrue();

    $this->assertAuthenticatedAs($user);
});

it('normalizes wildcard session domains into browser-valid parent domains', function () {
    expect(config('session.domain'))->toBe('.example.test');
});

it('assigns the chosen portal role when registering with a user type', function () {
    config(['multidomain.registerable_portals' => ['blog' => 'Blog Writer']]);
    Role::create(['name' => 'blog', 'guard_name' => 'web']);

    Livewire::test('pages::auth.register')
        ->set('name', 'Taylor Otwell')
        ->set('email', 'taylor@example.com')
        ->set('password', 'Jrb!2026-Register-Flow-Q9v#72')
        ->set('password_confirmation', 'Jrb!2026-Register-Flow-Q9v#72')
        ->set('userType', 'blog')
        ->call('register')
        ->assertHasNoErrors();

    $user = User::where('email', 'taylor@example.com')->first();

    expect($user->hasRole('blog'))->toBeTrue();
});

it('rejects a user type that is not in the registerable portals list', function () {
    config(['multidomain.registerable_portals' => ['blog' => 'Blog Writer']]);

    Livewire::test('pages::auth.register')
        ->set('name', 'Taylor Otwell')
        ->set('email', 'taylor@example.com')
        ->set('password', 'Jrb!2026-Register-Flow-Q9v#72')
        ->set('password_confirmation', 'Jrb!2026-Register-Flow-Q9v#72')
        ->set('userType', 'not-a-real-portal')
        ->call('register')
        ->assertHasErrors('userType');
});

it('does not assign any role when no user type is selected', function () {
    config(['multidomain.registerable_portals' => ['blog' => 'Blog Writer']]);

    Livewire::test('pages::auth.register')
        ->set('name', 'Taylor Otwell')
        ->set('email', 'taylor@example.com')
        ->set('password', 'Jrb!2026-Register-Flow-Q9v#72')
        ->set('password_confirmation', 'Jrb!2026-Register-Flow-Q9v#72')
        ->call('register')
        ->assertHasNoErrors();

    $user = User::where('email', 'taylor@example.com')->first();

    expect($user->getRoleNames())->toBeEmpty();
});
