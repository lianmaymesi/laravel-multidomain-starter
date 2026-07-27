<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

it('generates a secret and qr code on mount', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::accounts.two-factor-setup')
        ->assertSet('confirmed', false)
        ->assertSet('qrCodeSvg', fn ($svg) => str_contains($svg, '<svg'));

    expect($user->fresh()->two_factor_secret)->not->toBeNull();
});

it('shows as already confirmed for a user with two-factor already enabled', function () {
    $user = User::factory()->create([
        'two_factor_secret' => encrypt('SOMESECRETXXXXXXXX'),
        'two_factor_enabled_at' => now(),
        'two_factor_confirmed_at' => now(),
    ]);

    Livewire::actingAs($user)
        ->test('pages::accounts.two-factor-setup')
        ->assertSet('confirmed', true);
});

it('confirms two-factor with a valid code and shows recovery codes', function () {
    $user = User::factory()->create();

    $component = Livewire::actingAs($user)->test('pages::accounts.two-factor-setup');

    $code = (new Google2FA)->getCurrentOtp(decrypt($user->fresh()->two_factor_secret));

    $component->set('code', $code)
        ->call('confirm')
        ->assertSet('confirmed', true)
        ->assertSet('showRecoveryCodes', true);

    expect($user->fresh()->hasTwoFactorEnabled())->toBeTrue();
});

it('rejects an invalid confirmation code', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::accounts.two-factor-setup')
        ->set('code', '000000')
        ->call('confirm')
        ->assertHasErrors('code');

    expect($user->fresh()->hasTwoFactorEnabled())->toBeFalse();
});

it('disables two-factor and rotates the secret', function () {
    $user = User::factory()->create([
        'two_factor_secret' => encrypt('SOMESECRETXXXXXXXX'),
        'two_factor_enabled_at' => now(),
        'two_factor_confirmed_at' => now(),
    ]);

    $originalSecret = $user->two_factor_secret;

    Livewire::actingAs($user)
        ->test('pages::accounts.two-factor-setup')
        ->call('disable')
        ->assertSet('confirmed', false);

    $user->refresh();

    expect($user->hasTwoFactorEnabled())->toBeFalse()
        ->and($user->two_factor_secret)->not->toBe($originalSecret);
});
