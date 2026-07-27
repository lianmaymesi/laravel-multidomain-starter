<?php

use App\Models\AccountDeletionRequest;
use App\Models\User;
use App\Services\Auth\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

function twoFactorEnabledUser(array $overrides = []): User
{
    $secret = (new Google2FA)->generateSecretKey(32);

    return User::factory()->create(array_merge([
        'country_code' => '+91',
        'phone' => '9876543210',
        'two_factor_secret' => encrypt($secret),
        'two_factor_enabled_at' => now(),
        'two_factor_confirmed_at' => now(),
    ], $overrides));
}

it('renders the two factor challenge in authenticator and recovery modes', function () {
    $user = User::factory()->create([
        'country_code' => '+91',
        'phone' => '9876543210',
    ]);

    session(['2fa_user_id' => $user->id]);

    Livewire::test('pages::auth.two-factor-challenge')
        ->assertSee('Two-factor check')
        ->assertSee('Authenticator code required')
        ->assertSee('Use a recovery code instead')
        ->call('toggleRecovery')
        ->assertSee('Use a recovery code')
        ->assertSee('Recovery Code')
        ->assertSee('Use authenticator app instead');
});

it('redirects to login when there is no pending two-factor session', function () {
    Livewire::test('pages::auth.two-factor-challenge')
        ->assertRedirect(route('auth.login'));
});

it('logs the user in and redirects to their portal on a valid code', function () {
    $user = twoFactorEnabledUser(['phone_verified_at' => now()]);
    $code = (new Google2FA)->getCurrentOtp(decrypt($user->two_factor_secret));

    session(['2fa_user_id' => $user->id]);

    Livewire::test('pages::auth.two-factor-challenge')
        ->set('code', $code)
        ->call('verify')
        ->assertRedirect($user->redirect());

    $this->assertAuthenticatedAs($user);
    expect(session('2fa_user_id'))->toBeNull();
});

it('rejects an invalid authenticator code', function () {
    $user = twoFactorEnabledUser();

    session(['2fa_user_id' => $user->id]);

    Livewire::test('pages::auth.two-factor-challenge')
        ->set('code', '000000')
        ->call('verify')
        ->assertHasErrors('code');

    $this->assertGuest();
});

it('logs the user in with a valid recovery code and consumes it', function () {
    $user = twoFactorEnabledUser(['phone_verified_at' => now()]);
    app(TwoFactorService::class)->regenerateRecoveryCodes($user);
    $recoveryCode = $user->fresh()->twoFactorRecoveryCodes()[0];

    session(['2fa_user_id' => $user->id]);

    Livewire::test('pages::auth.two-factor-challenge')
        ->call('toggleRecovery')
        ->set('code', $recoveryCode)
        ->call('verify')
        ->assertRedirect($user->redirect());

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->twoFactorRecoveryCodes())->not->toContain($recoveryCode);
});

it('cancels an active deletion request and redirects to the account portal', function () {
    $user = twoFactorEnabledUser(['phone_verified_at' => now()]);
    $code = (new Google2FA)->getCurrentOtp(decrypt($user->two_factor_secret));

    $request = $user->deletionRequest()->create([
        'requested_at' => now(),
        'scheduled_at' => now()->addDays(AccountDeletionRequest::GRACE_PERIOD_DAYS),
        'status' => 'pending',
    ]);

    session(['2fa_user_id' => $user->id]);

    Livewire::test('pages::auth.two-factor-challenge')
        ->set('code', $code)
        ->call('verify')
        ->assertRedirect(route('account.index'));

    expect($request->fresh()->status)->toBe('cancelled');
});
