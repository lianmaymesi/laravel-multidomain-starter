<?php

namespace App\Contracts;

interface SmsService
{
    /**
     * Send an OTP SMS to a phone number.
     *
     * @param  string  $to  E.164 format e.g. +919876543210
     * @param  string  $code  6-digit OTP
     */
    public function sendOtp(string $to, string $code): bool;
}
