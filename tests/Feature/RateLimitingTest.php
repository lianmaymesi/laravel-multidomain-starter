<?php

use App\Models\User;
use Atrium\Core\Contracts\SmsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

function throttledTwoFactorUser(): array
{
    $secret = (new Google2FA)->generateSecretKey(32);

    $user = User::factory()->create([
        'two_factor_secret' => encrypt($secret),
        'two_factor_enabled_at' => now(),
        'two_factor_confirmed_at' => now(),
    ]);

    return [$user, $secret];
}

function failTwoFactor(User $user, int $times): void
{
    foreach (range(1, $times) as $_) {
        session(['2fa_user_id' => $user->id]);
        Livewire::test('pages::auth.two-factor-challenge')->set('code', '000000')->call('verify');
    }
}

// ── 2FA challenge ────────────────────────────────────────────────────

it('locks the 2FA challenge after repeated wrong codes, even for the right code', function () {
    [$user, $secret] = throttledTwoFactorUser();

    failTwoFactor($user, 5);

    session(['2fa_user_id' => $user->id]);
    Livewire::test('pages::auth.two-factor-challenge')
        ->set('code', (new Google2FA)->getCurrentOtp($secret))
        ->call('verify')
        ->assertHasErrors(['code'])
        ->assertSee('Too many attempts');

    $this->assertGuest();
});

it('limits 2FA guesses per IP across accounts', function () {
    config(['rate-limits.two-factor-ip.max' => 3]);
    [$a] = throttledTwoFactorUser();
    [$b, $secret] = throttledTwoFactorUser();

    failTwoFactor($a, 3);

    session(['2fa_user_id' => $b->id]);
    Livewire::test('pages::auth.two-factor-challenge')
        ->set('code', (new Google2FA)->getCurrentOtp($secret))
        ->call('verify')
        ->assertHasErrors(['code']);
});

it('forgives earlier 2FA failures after a successful challenge', function () {
    [$user, $secret] = throttledTwoFactorUser();

    failTwoFactor($user, 4);

    session(['2fa_user_id' => $user->id]);
    Livewire::test('pages::auth.two-factor-challenge')
        ->set('code', (new Google2FA)->getCurrentOtp($secret))
        ->call('verify')
        ->assertHasNoErrors();

    $this->assertAuthenticatedAs($user);
});

// ── Login ────────────────────────────────────────────────────────────

it('caps failed logins per IP across different emails (credential stuffing)', function () {
    config(['rate-limits.login-ip.max' => 3]);
    $victim = User::factory()->create(['password' => Hash::make('correct-password')]);

    foreach (['a@x.test', 'b@x.test', 'c@x.test'] as $email) {
        Livewire::test('pages::auth.login')->set('email', $email)->set('password', 'nope')->call('login');
    }

    Livewire::test('pages::auth.login')
        ->set('email', $victim->email)
        ->set('password', 'correct-password')
        ->call('login')
        ->assertHasErrors(['email'])
        ->assertSee('Too many attempts');

    $this->assertGuest();
});

// ── Register / password reset ────────────────────────────────────────

it('limits new accounts per IP', function () {
    config(['rate-limits.register.max' => 1]);
    $sms = Mockery::mock(SmsService::class);
    $sms->shouldReceive('sendOtp')->andReturnTrue();
    $this->app->instance(SmsService::class, $sms);

    Livewire::test('pages::auth.register')
        ->set('name', 'First')
        ->set('email', 'first@example.com')
        ->set('password', 'Jrb!2026-Register-Flow-Q9v#72')
        ->set('password_confirmation', 'Jrb!2026-Register-Flow-Q9v#72')
        ->set('country_code', '+91')
        ->set('phone', '9876543211')
        ->call('register')
        ->assertHasNoErrors();
    auth()->logout();

    Livewire::test('pages::auth.register')
        ->set('name', 'Taylor Otwell')
        ->set('email', 'taylor@example.com')
        ->set('password', 'Jrb!2026-Register-Flow-Q9v#72')
        ->set('password_confirmation', 'Jrb!2026-Register-Flow-Q9v#72')
        ->set('country_code', '+91')
        ->set('phone', '9876543210')
        ->call('register')
        ->assertHasErrors(['email']);

    expect(User::where('email', 'taylor@example.com')->exists())->toBeFalse();
});

it('limits password-reset code requests per IP across recipients', function () {
    Notification::fake();
    config(['rate-limits.password-reset-request.max' => 2]);

    foreach (['one@x.test', 'two@x.test'] as $email) {
        Livewire::test('pages::auth.forgot-password')->set('activeTab', 'email')->set('email', $email)->call('sendOtp')->assertHasNoErrors();
    }

    Livewire::test('pages::auth.forgot-password')
        ->set('activeTab', 'email')
        ->set('email', 'three@x.test')
        ->call('sendOtp')
        ->assertHasErrors(['email'])
        ->assertSet('otpSent', false);
});

it('shows the per-recipient resend limit on the field the user can see', function () {
    Notification::fake();

    Livewire::test('pages::auth.forgot-password')->set('activeTab', 'email')->set('email', 'one@x.test')->call('sendOtp');

    // Same recipient again, inside the resend cooldown.
    Livewire::test('pages::auth.forgot-password')
        ->set('activeTab', 'email')
        ->set('email', 'one@x.test')
        ->call('sendOtp')
        ->assertHasErrors(['email'])
        ->assertHasNoErrors(['code']);
});

it('limits password-reset submissions per IP', function () {
    config(['rate-limits.password-reset.max' => 1]);
    $user = User::factory()->create();

    Livewire::test('pages::auth.reset-password', ['token' => Password::createToken($user), 'email' => $user->email])
        ->set('password', 'Jrb!2026-Reset-Flow-Q9v#72')
        ->set('password_confirmation', 'Jrb!2026-Reset-Flow-Q9v#72')
        ->call('resetPassword')
        ->assertHasNoErrors();

    $token = Password::createToken($user);

    Livewire::test('pages::auth.reset-password', ['token' => $token, 'email' => $user->email])
        ->set('password', 'Jrb!2026-Reset-Flow-Q9v#72')
        ->set('password_confirmation', 'Jrb!2026-Reset-Flow-Q9v#72')
        ->call('resetPassword')
        ->assertHasErrors(['password']);
});

// ── Account ──────────────────────────────────────────────────────────

it('limits wrong current-password guesses when changing the password', function () {
    $user = User::factory()->create(['password' => Hash::make('Old!Password-2026-x9')]);

    foreach (range(1, 5) as $_) {
        Livewire::actingAs($user)->test('pages::accounts.security')
            ->set('current_password', 'wrong')
            ->set('password', 'New!Password-2026-x9')
            ->set('password_confirmation', 'New!Password-2026-x9')
            ->call('updatePassword');
    }

    Livewire::actingAs($user)->test('pages::accounts.security')
        ->set('current_password', 'Old!Password-2026-x9')
        ->set('password', 'New!Password-2026-x9')
        ->set('password_confirmation', 'New!Password-2026-x9')
        ->call('updatePassword')
        ->assertHasErrors(['current_password']);

    expect(Hash::check('Old!Password-2026-x9', $user->fresh()->password))->toBeTrue();
});

it('does not count other validation errors against the current-password limit', function () {
    config(['rate-limits.current-password.max' => 1]);
    $user = User::factory()->create(['password' => Hash::make('Old!Password-2026-x9')]);

    // Right current password, mismatched confirmation — not a guess.
    Livewire::actingAs($user)->test('pages::accounts.security')
        ->set('current_password', 'Old!Password-2026-x9')
        ->set('password', 'New!Password-2026-x9')
        ->set('password_confirmation', 'different')
        ->call('updatePassword')
        ->assertHasErrors(['password']);

    Livewire::actingAs($user)->test('pages::accounts.security')
        ->set('current_password', 'Old!Password-2026-x9')
        ->set('password', 'New!Password-2026-x9')
        ->set('password_confirmation', 'New!Password-2026-x9')
        ->call('updatePassword')
        ->assertHasNoErrors();
});

it('limits wrong codes while confirming authenticator setup', function () {
    config(['rate-limits.two-factor-setup.max' => 2]);
    $user = User::factory()->create();

    $page = Livewire::actingAs($user)->test('pages::accounts.two-factor-setup');
    $page->set('code', '000000')->call('confirm')->set('code', '000000')->call('confirm');

    $page->set('code', '000000')->call('confirm')->assertSee('Too many attempts');
});

it('limits email-change requests so the form cannot spam an address', function () {
    Notification::fake();
    config(['rate-limits.email-change.max' => 2]);
    $user = User::factory()->create();

    foreach (['a@x.test', 'b@x.test'] as $email) {
        Livewire::actingAs($user)->test('pages::accounts.index')->call('editEmail')->set('newEmail', $email)->call('requestEmailChange')->assertHasNoErrors();
    }

    Livewire::actingAs($user)->test('pages::accounts.index')
        ->call('editEmail')
        ->set('newEmail', 'c@x.test')
        ->call('requestEmailChange')
        ->assertHasErrors(['newEmail']);

    Livewire::actingAs($user->fresh())->test('pages::accounts.index')
        ->call('resendEmailVerification')
        ->assertHasErrors(['emailResend']);
});

it('limits phone-number changes, which each send an SMS', function () {
    config(['multidomain.phone_verification_enabled' => true, 'rate-limits.phone-change.max' => 1]);
    $user = User::factory()->create(['country_code' => '+91', 'phone' => '9876543210', 'phone_verified_at' => now()]);

    $sms = Mockery::mock(SmsService::class);
    $sms->shouldReceive('sendOtp')->once()->andReturnTrue();
    $this->app->instance(SmsService::class, $sms);

    Livewire::actingAs($user)->test('pages::accounts.index')->call('editPhone')->set('newPhone', '9999999999')->call('savePhone');

    Livewire::actingAs($user->fresh())->test('pages::accounts.index')
        ->call('editPhone')
        ->set('newPhone', '8888888888')
        ->call('savePhone')
        ->assertHasErrors(['newPhone']);

    expect($user->fresh()->phone)->toBe('9999999999');
});

// ── HTTP routes ──────────────────────────────────────────────────────

it('answers 429 with Retry-After once a token link is hammered', function () {
    config(['rate-limits.links.max' => 2]);

    foreach (range(1, 2) as $_) {
        $this->get(route('auth.verify-email-change', ['token' => 'guess']))->assertStatus(200);
    }

    $this->get(route('auth.verify-email-change', ['token' => 'guess']))
        ->assertStatus(429)
        ->assertHeader('Retry-After');
});

it('throttles export downloads', function () {
    config(['rate-limits.downloads.max' => 1]);

    $this->get(route('account.export.download', ['token' => 'nope', 'dt' => 'x']))->assertNotFound();
    $this->get(route('account.export.download', ['token' => 'nope', 'dt' => 'x']))->assertStatus(429);
});

it('words the wait in minutes, not raw seconds', function () {
    [$user] = throttledTwoFactorUser();
    failTwoFactor($user, 5);

    session(['2fa_user_id' => $user->id]);
    Livewire::test('pages::auth.two-factor-challenge')
        ->set('code', '000000')
        ->call('verify')
        ->assertSeeText('Too many attempts. Please try again in ')
        ->assertSeeText('minute');
});
