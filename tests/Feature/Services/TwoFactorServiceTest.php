<?php

use App\Models\User;
use App\Services\Auth\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

it('generates and stores an encrypted secret', function () {
    $user = User::factory()->create();

    $secret = app(TwoFactorService::class)->generateSecret($user);

    expect(decrypt($user->fresh()->two_factor_secret))->toBe($secret)
        ->and($user->fresh()->hasTwoFactorEnabled())->toBeFalse();
});

it('renders qr code svg markup', function () {
    $user = User::factory()->create();
    app(TwoFactorService::class)->generateSecret($user);

    $svg = app(TwoFactorService::class)->qrCodeSvg($user->fresh());

    expect($svg)->toContain('<svg');
});

it('confirms two-factor with a valid code and generates recovery codes', function () {
    $user = User::factory()->create();
    $service = app(TwoFactorService::class);
    $secret = $service->generateSecret($user);

    $code = (new Google2FA)->getCurrentOtp($secret);

    expect($service->confirm($user->fresh(), $code))->toBeTrue();

    $user->refresh();
    expect($user->hasTwoFactorEnabled())->toBeTrue()
        ->and($user->twoFactorRecoveryCodes())->toHaveCount(8);
});

it('refuses to confirm with an invalid code', function () {
    $user = User::factory()->create();
    $service = app(TwoFactorService::class);
    $service->generateSecret($user);

    expect($service->confirm($user->fresh(), '000000'))->toBeFalse();
    expect($user->fresh()->hasTwoFactorEnabled())->toBeFalse();
});

it('verifies a valid totp code during login challenge', function () {
    $user = User::factory()->create();
    $service = app(TwoFactorService::class);
    $secret = $service->generateSecret($user);

    $code = (new Google2FA)->getCurrentOtp($secret);

    expect($service->verify($user->fresh(), $code))->toBeTrue();
    expect($service->verify($user->fresh(), '000000'))->toBeFalse();
});

it('verifies and consumes a valid recovery code', function () {
    $user = User::factory()->create();
    $service = app(TwoFactorService::class);
    $service->generateSecret($user);
    $service->regenerateRecoveryCodes($user->fresh());

    $codes = $user->fresh()->twoFactorRecoveryCodes();
    $codeToUse = $codes[0];

    expect($service->verifyRecoveryCode($user->fresh(), $codeToUse))->toBeTrue();

    $remaining = $user->fresh()->twoFactorRecoveryCodes();
    expect($remaining)->not->toContain($codeToUse)
        ->and($remaining)->toHaveCount(count($codes) - 1);

    expect($service->verifyRecoveryCode($user->fresh(), $codeToUse))->toBeFalse();
});

it('disables two-factor and clears secrets', function () {
    $user = User::factory()->create();
    $service = app(TwoFactorService::class);
    $secret = $service->generateSecret($user);
    $service->confirm($user->fresh(), (new Google2FA)->getCurrentOtp($secret));

    $service->disable($user->fresh());

    $user->refresh();
    expect($user->two_factor_secret)->toBeNull()
        ->and($user->two_factor_recovery_codes)->toBeNull()
        ->and($user->hasTwoFactorEnabled())->toBeFalse();
});
