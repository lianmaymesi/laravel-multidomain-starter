<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Email Verification Grace Period
    |--------------------------------------------------------------------------
    |
    | Number of days a user can access their panel without verifying their
    | email address. After this period the middleware hard-blocks access
    | and redirects to the email verification page.
    |
    */

    'email_verification_grace_days' => env('EMAIL_VERIFICATION_GRACE_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | OTP Settings
    |--------------------------------------------------------------------------
    |
    |
    |
    */

    'otp' => [
        'expires_minutes' => env('OTP_EXPIRES_MINUTES', 10),
        'max_attempts' => 5,
        'resend_cooldown' => 60, // seconds
        'resend_max_attempts' => 3,
        'resend_lockout_seconds' => 60 * 60 * 24,
    ],
];
