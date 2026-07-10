<?php

use App\Enums\OtpType;
use App\Services\Auth\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

it('blocks forgot password otp requests after three attempts for twenty four hours', function () {
    $otpService = app(OtpService::class);
    $recipient = '+919876543210';
    $type = OtpType::PHONE_FORGOT_PASSWORD;

    foreach (range(1, 3) as $attempt) {
        $otpService->gateResend($recipient, $type);

        if ($attempt < 3) {
            $this->travel(config('multidomain.otp.resend_cooldown') + 1)->seconds();
        }
    }

    try {
        $otpService->gateResend($recipient, $type);

        $this->fail('The resend gate should lock after three OTP requests.');
    } catch (ValidationException $exception) {
        expect($exception->errors()['code'][0])
            ->toContain('maximum number of OTP requests')
            ->toContain('24 hours');
    }
});

it('allows new forgot password otp requests again after the twenty four hour lock expires', function () {
    $otpService = app(OtpService::class);
    $recipient = 'user@example.com';
    $type = OtpType::EMAIL_FORGOT_PASSWORD;

    foreach (range(1, 3) as $attempt) {
        $otpService->gateResend($recipient, $type);

        if ($attempt < 3) {
            $this->travel(config('multidomain.otp.resend_cooldown') + 1)->seconds();
        }
    }

    $this->travel(config('multidomain.otp.resend_lockout_seconds') + 1)->seconds();

    $otpService->gateResend($recipient, $type);

    expect(true)->toBeTrue();
});
