<?php

use App\Services\Auth\TwilioSmsService;
use Illuminate\Support\Facades\Log;

it('returns false and logs a warning when twilio credentials are not configured', function () {
    config(['services.twilio.sid' => null, 'services.twilio.token' => null]);

    Log::shouldReceive('warning')->once()->with(
        'TwilioSmsService: Twilio is not configured, skipping OTP SMS.',
        ['to' => '+919876543210']
    );

    $result = (new TwilioSmsService)->sendOtp('+919876543210', '123456');

    expect($result)->toBeFalse();
});
