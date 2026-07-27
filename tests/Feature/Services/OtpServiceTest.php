<?php

use App\Enums\OtpType;
use App\Models\OtpCode;
use App\Models\User;
use App\Services\Auth\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('generates a 6-digit otp and invalidates previous unused ones of the same type', function () {
    $user = User::factory()->create();
    $service = app(OtpService::class);

    $first = $service->generate($user, OtpType::EMAIL_VERIFICATION);
    $second = $service->generate($user, OtpType::EMAIL_VERIFICATION);

    expect($second->code)->toMatch('/^\d{6}$/')
        ->and($first->fresh()->used_at)->not->toBeNull()
        ->and($second->fresh()->used_at)->toBeNull();
});

it('validates a correct otp and marks it used', function () {
    $user = User::factory()->create();
    $otp = app(OtpService::class)->generate($user, OtpType::EMAIL_VERIFICATION);

    app(OtpService::class)->validate($user, OtpType::EMAIL_VERIFICATION, $otp->code);

    expect($otp->fresh()->used_at)->not->toBeNull();
});

it('rejects an incorrect otp code', function () {
    $user = User::factory()->create();
    app(OtpService::class)->generate($user, OtpType::EMAIL_VERIFICATION);

    app(OtpService::class)->validate($user, OtpType::EMAIL_VERIFICATION, '000000');
})->throws(ValidationException::class);

it('rejects an expired otp code', function () {
    $user = User::factory()->create();
    $otp = OtpCode::create([
        'user_id' => $user->id,
        'type' => OtpType::EMAIL_VERIFICATION->value,
        'code' => '123456',
        'expires_at' => now()->subMinute(),
        'used_at' => null,
    ]);

    app(OtpService::class)->validate($user, OtpType::EMAIL_VERIFICATION, $otp->code);
})->throws(ValidationException::class);

it('locks out otp validation after five failed attempts', function () {
    $user = User::factory()->create();
    $otp = app(OtpService::class)->generate($user, OtpType::EMAIL_VERIFICATION);

    foreach (range(1, 5) as $attempt) {
        try {
            app(OtpService::class)->validate($user, OtpType::EMAIL_VERIFICATION, '000000');
        } catch (ValidationException) {
            // expected
        }
    }

    app(OtpService::class)->validate($user, OtpType::EMAIL_VERIFICATION, $otp->code);
})->throws(ValidationException::class, 'Too many attempts');

it('reports zero resend cooldown when none has been requested', function () {
    $user = User::factory()->create();

    expect(app(OtpService::class)->resendCooldown($user, OtpType::EMAIL_VERIFICATION))->toBe(0);
});
