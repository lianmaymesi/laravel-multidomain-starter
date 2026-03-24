<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client as TwilioClient;

class SmsService
{
    protected TwilioClient $client;

    public function __construct()
    {
        $this->client = new TwilioClient(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
    }

    /**
     * Send an OTP SMS to a phone number.
     *
     * @param  string  $to  E.164 format e.g. +919876543210
     * @param  string  $code  6-digit OTP
     */
    public function sendOtp(string $to, string $code): bool
    {
        try {
            $this->client->messages->create($to, [
                'from' => config('services.twilio.from'),
                'body' => $this->otpMessage($code),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('SmsService: failed to send OTP', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function otpMessage(string $code): string
    {
        $appName = config('app.name');

        return "{$code} is your {$appName} verification code. Valid for 10 minutes. Do not share it with anyone.";
    }
}
