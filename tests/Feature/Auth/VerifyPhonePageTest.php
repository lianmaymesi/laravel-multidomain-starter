<?php

use App\Contracts\SmsService;
use App\Enums\OtpType;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function verifyPhoneUser(array $overrides = []): User
{
    return User::factory()->create(array_merge([
        'country_code' => '+91',
        'phone' => '9876543210',
        'phone_verified_at' => null,
    ], $overrides));
}

it('redirects away when phone verification is disabled', function () {
    config(['multidomain.phone_verification_enabled' => false]);

    $user = verifyPhoneUser();

    Livewire::actingAs($user)
        ->test('pages::auth.verify-phone')
        ->assertRedirect($user->redirect());
});

it('verifies the phone with a correct code and redirects to the portal', function () {
    config(['multidomain.phone_verification_enabled' => true]);

    $user = verifyPhoneUser();

    $otp = OtpCode::create([
        'user_id' => $user->id,
        'type' => OtpType::PHONE_VERIFICATION->value,
        'code' => '123456',
        'expires_at' => now()->addMinutes(10),
        'used_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test('pages::auth.verify-phone')
        ->set('code', $otp->code)
        ->call('verify')
        ->assertRedirect($user->redirect());

    expect($user->fresh()->hasVerifiedPhone())->toBeTrue();
});

it('sends a verified two-factor user to the challenge instead of their portal', function () {
    config(['multidomain.phone_verification_enabled' => true]);

    $user = verifyPhoneUser([
        'two_factor_secret' => encrypt('secret'),
        'two_factor_enabled_at' => now(),
        'two_factor_confirmed_at' => now(),
    ]);

    $otp = OtpCode::create([
        'user_id' => $user->id,
        'type' => OtpType::PHONE_VERIFICATION->value,
        'code' => '123456',
        'expires_at' => now()->addMinutes(10),
        'used_at' => null,
    ]);

    Livewire::actingAs($user)
        ->test('pages::auth.verify-phone')
        ->set('code', $otp->code)
        ->call('verify')
        ->assertRedirect(route('auth.two-factor-challenge'));
});

it('rejects an invalid verification code', function () {
    config(['multidomain.phone_verification_enabled' => true]);

    $user = verifyPhoneUser();

    Livewire::actingAs($user)
        ->test('pages::auth.verify-phone')
        ->set('code', 111111)
        ->call('verify')
        ->assertHasErrors('code');

    expect($user->fresh()->hasVerifiedPhone())->toBeFalse();
});

it('resends the otp and decrements the resend attempts left', function () {
    config(['multidomain.phone_verification_enabled' => true]);

    $user = verifyPhoneUser();

    $smsService = Mockery::mock(SmsService::class);
    $smsService->shouldReceive('sendOtp')->once()->andReturnTrue();
    $this->app->instance(SmsService::class, $smsService);

    Livewire::actingAs($user)
        ->test('pages::auth.verify-phone')
        ->call('resend')
        ->assertSet('resendAttemptsLeft', 1);
});

it('updates the phone number and sends a fresh otp', function () {
    config(['multidomain.phone_verification_enabled' => true]);

    $user = verifyPhoneUser();

    $smsService = Mockery::mock(SmsService::class);
    $smsService->shouldReceive('sendOtp')->once()->with('+919999999999', Mockery::pattern('/^\d{6}$/'))->andReturnTrue();
    $this->app->instance(SmsService::class, $smsService);

    Livewire::actingAs($user)
        ->test('pages::auth.verify-phone')
        ->call('startEdit')
        ->set('newCountryCode', '+91')
        ->set('newPhone', '9999999999')
        ->call('updatePhone')
        ->assertSet('editAttemptsLeft', 1);

    expect($user->fresh())
        ->phone->toBe('9999999999')
        ->phone_verified_at->toBeNull();
});
