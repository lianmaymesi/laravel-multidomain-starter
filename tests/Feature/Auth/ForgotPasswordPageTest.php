<?php

use App\Contracts\SmsService;
use App\Enums\OtpType;
use App\Models\OtpCode;
use App\Models\User;
use App\Notifications\ForgotPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('defaults to the email tab when phone verification is disabled', function () {
    config(['multidomain.phone_verification_enabled' => false]);

    Livewire::test('pages::auth.forgot-password')
        ->assertSet('activeTab', 'email');
});

it('sends a phone otp and moves to the code step', function () {
    config(['multidomain.phone_verification_enabled' => true]);

    $user = User::factory()->create(['country_code' => '+91', 'phone' => '9876543210']);

    $smsService = Mockery::mock(SmsService::class);
    $smsService->shouldReceive('sendOtp')->once()->with('+919876543210', Mockery::pattern('/^\d{6}$/'))->andReturnTrue();
    $this->app->instance(SmsService::class, $smsService);

    Livewire::test('pages::auth.forgot-password')
        ->set('activeTab', 'phone')
        ->set('country_code', '+91')
        ->set('phone', '9876543210')
        ->call('sendOtp')
        ->assertSet('otpSent', true);

    $this->assertDatabaseHas('otp_codes', [
        'user_id' => $user->id,
        'type' => OtpType::PHONE_FORGOT_PASSWORD->value,
    ]);
});

it('sends an email otp notification and moves to the code step', function () {
    Notification::fake();

    $user = User::factory()->create();

    Livewire::test('pages::auth.forgot-password')
        ->set('activeTab', 'email')
        ->set('email', $user->email)
        ->call('sendOtp')
        ->assertSet('otpSent', true);

    Notification::assertSentTo($user, ForgotPassword::class);
});

it('shows success without leaking whether the account exists', function () {
    Livewire::test('pages::auth.forgot-password')
        ->set('activeTab', 'email')
        ->set('email', 'nobody@example.com')
        ->call('sendOtp')
        ->assertSet('otpSent', true);
});

it('verifies the otp and redirects to reset password with a valid token', function () {
    $user = User::factory()->create();

    $otp = OtpCode::create([
        'user_id' => $user->id,
        'type' => OtpType::EMAIL_FORGOT_PASSWORD->value,
        'code' => '123456',
        'expires_at' => now()->addMinutes(10),
        'used_at' => null,
    ]);

    Livewire::test('pages::auth.forgot-password')
        ->set('activeTab', 'email')
        ->set('email', $user->email)
        ->set('code', $otp->code)
        ->call('verifyOtp')
        ->assertRedirectContains('reset-password');

    $this->assertDatabaseHas('password_reset_tokens', ['email' => $user->email]);
});

it('rejects an invalid otp code', function () {
    $user = User::factory()->create();

    OtpCode::create([
        'user_id' => $user->id,
        'type' => OtpType::EMAIL_FORGOT_PASSWORD->value,
        'code' => '123456',
        'expires_at' => now()->addMinutes(10),
        'used_at' => null,
    ]);

    Livewire::test('pages::auth.forgot-password')
        ->set('activeTab', 'email')
        ->set('email', $user->email)
        ->set('code', '000000')
        ->call('verifyOtp')
        ->assertHasErrors('code');
});
