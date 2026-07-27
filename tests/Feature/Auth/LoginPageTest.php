<?php

use App\Contracts\SmsService;
use App\Models\AccountDeletionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function loginTestUser(array $overrides = []): User
{
    return User::factory()->create(array_merge([
        'country_code' => '+91',
        'phone' => '9876543210',
        'password' => bcrypt('correct-password'),
    ], $overrides));
}

it('logs in a verified user and redirects to their portal', function () {
    $user = loginTestUser([
        'phone_verified_at' => now(),
    ]);

    Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('password', 'correct-password')
        ->call('login')
        ->assertRedirect($user->redirect());

    $this->assertAuthenticatedAs($user);
});

it('rejects invalid credentials', function () {
    $user = loginTestUser();

    Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

it('sends the user to phone verification when the phone is unverified', function () {
    config(['multidomain.phone_verification_enabled' => true]);

    $user = loginTestUser(['phone_verified_at' => null]);

    $smsService = Mockery::mock(SmsService::class);
    $smsService->shouldReceive('sendOtp')->once()->with($user->fullPhone(), Mockery::pattern('/^\d{6}$/'))->andReturnTrue();
    $this->app->instance(SmsService::class, $smsService);

    Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('password', 'correct-password')
        ->call('login')
        ->assertRedirect(route('auth.verify-phone'));

    $this->assertAuthenticatedAs($user);
});

it('sends a two-factor enabled user to the challenge and logs them out until it passes', function () {
    $user = loginTestUser([
        'phone_verified_at' => now(),
        'two_factor_secret' => encrypt('secret'),
        'two_factor_enabled_at' => now(),
        'two_factor_confirmed_at' => now(),
    ]);

    Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('password', 'correct-password')
        ->call('login')
        ->assertRedirect(route('auth.two-factor-challenge'));

    $this->assertGuest();
    expect(session('2fa_user_id'))->toBe($user->id);
});

it('cancels an active deletion request and redirects to the account portal on login', function () {
    $user = loginTestUser(['phone_verified_at' => now()]);

    $request = $user->deletionRequest()->create([
        'requested_at' => now(),
        'scheduled_at' => now()->addDays(AccountDeletionRequest::GRACE_PERIOD_DAYS),
        'status' => 'pending',
    ]);

    Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('password', 'correct-password')
        ->call('login')
        ->assertRedirect(route('account.index'));

    expect($request->fresh()->status)->toBe('cancelled');
});

it('throttles repeated failed login attempts', function () {
    $user = loginTestUser();

    RateLimiter::clear('login:'.strtolower($user->email).'|127.0.0.1');

    foreach (range(1, 5) as $attempt) {
        Livewire::test('pages::auth.login')
            ->set('email', $user->email)
            ->set('password', 'wrong-password')
            ->call('login');
    }

    Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('password', 'correct-password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});
