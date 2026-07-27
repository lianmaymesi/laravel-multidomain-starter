<?php

namespace App\Services\Auth;

use App\Contracts\SmsService;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

/**
 * Default SmsService implementation, backed by Twilio.
 *
 * To use a different provider, implement App\Contracts\SmsService and
 * rebind it in App\Providers\AppServiceProvider::register().
 */
class TwilioSmsService implements SmsService
{
    public function sendOtp(string $to, string $code): bool
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');

        if (! $sid || ! $token) {
            Log::warning('TwilioSmsService: Twilio is not configured, skipping OTP SMS.', ['to' => $to]);

            return false;
        }

        try {
            (new TwilioClient($sid, $token))->messages->create($to, [
                'from' => config('services.twilio.from'),
                'body' => $this->otpMessage($code),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('TwilioSmsService: failed to send OTP', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function otpMessage(string $code): string
    {
        $appName = config('app.name');
        $expires = config('multidomain.otp.expires_minutes', 10);

        return "{$code} is your {$appName} verification code. Valid for {$expires} minutes. Do not share it with anyone.";
    }
}
