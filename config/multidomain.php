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

    'main_domain' => env('APP_MAIN_DOMAIN', 'localhost'),

    /*
    |--------------------------------------------------------------------------
    | Single Domain Mode
    |--------------------------------------------------------------------------
    |
    | When true, every portal below resolves to the main domain instead of
    | its own subdomain. Route::domain() groups still register separately,
    | they just all match the same host — no route file changes needed.
    | Route URIs across portals must not collide when this is enabled.
    |
    */

    'single_domain' => (bool) env('APP_SINGLE_DOMAIN', false),

    /*
    |--------------------------------------------------------------------------
    | App's Sub Domain Setups
    |--------------------------------------------------------------------------
    |
    |
    |
    */

    'sub_domains' => (bool) env('APP_SINGLE_DOMAIN', false)
        ? array_fill_keys(['app', 'backoffice', 'landing', 'account', 'auth', 'api'], env('APP_MAIN_DOMAIN'))
        : [
            'app' => 'app.'.env('APP_MAIN_DOMAIN'),
            'backoffice' => 'backoffice.'.env('APP_MAIN_DOMAIN'),
            'landing' => 'landing.'.env('APP_MAIN_DOMAIN'),
            'account' => 'account.'.env('APP_MAIN_DOMAIN'),
            'auth' => 'auth.'.env('APP_MAIN_DOMAIN'),
            'api' => 'api.'.env('APP_MAIN_DOMAIN'),
        ],

    /*
    |--------------------------------------------------------------------------
    | Registerable Portals
    |--------------------------------------------------------------------------
    |
    | Portal roles a visitor can opt into from the public register page, shown
    | as a "Select the user type" dropdown. Key is the role name (matching a
    | subdomain scaffolded via `make:subdomain`), value is the label shown to
    | the visitor. Empty by default — the dropdown stays hidden and
    | registration behaves as before, with no role assigned.
    |
    */

    'registerable_portals' => [
        // 'blog' => 'Blog Writer',
    ],

    /*
    |--------------------------------------------------------------------------
    | Error & Maintenance Page Style
    |--------------------------------------------------------------------------
    |
    | Which error/maintenance view set each portal renders. Value is one of:
    |   'shared' — the one common design (resources/views/errors/shared/*)
    |   'own'    — the portal has its own set (resources/views/errors/{portal}/*)
    |   '<portal>' — reuse another portal's set verbatim, e.g. 'landing'
    |
    | See resources/views/errors/_dispatch.blade.php for the resolution logic.
    |
    */

    'page_style' => [
        'auth' => 'shared',
        'app' => 'shared',
        'backoffice' => 'shared',
        'account' => 'shared',
        'landing' => 'landing',
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
    | Phone Verification
    |--------------------------------------------------------------------------
    |
    | When disabled (the default), phone number collection is removed from
    | registration, the phone field becomes nullable, and every phone
    | verification gate (middleware, guest redirects, account settings)
    | is skipped — the app behaves as if phone/OTP never existed.
    |
    */

    'phone_verification_enabled' => (bool) env('PHONE_VERIFICATION_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Phone Country Code Mode
    |--------------------------------------------------------------------------
    |
    | 'single' — the whole app serves one country. The country code below
    | is applied automatically and the field is never shown to the user
    | (e.g. India = +91, Saudi Arabia = +966).
    |
    | 'multi' — users type their own country code as free text. There's no
    | validation against a real country list yet, so treat it as a plain
    | text field only — not full multi-country support.
    |
    */

    'phone_country_mode' => env('PHONE_COUNTRY_MODE', 'single'),

    'phone_default_country_code' => env('PHONE_DEFAULT_COUNTRY_CODE', '+91'),

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
