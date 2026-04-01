<?php

return [

    /*
    |--------------------------------------------------------------------------
    | App's Main Domain
    |--------------------------------------------------------------------------
    |
    |
    |
    */

    'main_domain' => env('JUST_READ_BIBLE_MAIN_DOMAIN', 'localhost'),

    /*
    |--------------------------------------------------------------------------
    | App's Sub Domain Setups
    |--------------------------------------------------------------------------
    |
    |
    |
    */

    'sub_domains' => [
        'app' => 'app.'.env('JUST_READ_BIBLE_MAIN_DOMAIN'),
        'backoffice' => 'backoffice.'.env('JUST_READ_BIBLE_MAIN_DOMAIN'),
        'landing' => 'landing.'.env('JUST_READ_BIBLE_MAIN_DOMAIN'),
        'account' => 'account.'.env('JUST_READ_BIBLE_MAIN_DOMAIN'),
        'auth' => 'auth.'.env('JUST_READ_BIBLE_MAIN_DOMAIN'),
        'api' => 'api.'.env('JUST_READ_BIBLE_MAIN_DOMAIN'),
    ],

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
