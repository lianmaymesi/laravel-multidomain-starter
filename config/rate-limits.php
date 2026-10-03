<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Rate Limits
    |--------------------------------------------------------------------------
    |
    | Every abuse guard in one place: "max" attempts per "decay" seconds.
    |
    | Livewire actions (login, 2FA, register, ...) are posted to
    | /livewire/update, so route `throttle` middleware never sees them — they
    | are checked inside the component with App\Concerns\ThrottlesActions.
    | Plain HTTP routes (downloads, emailed links) use the named limiters
    | registered in AppServiceProvider from the same values.
    |
    | OTP verify/resend limits live in config('multidomain.otp').
    |
    */

    // Failed logins per email + IP, and across all emails from one IP
    // (credential stuffing). Cleared on a successful login.
    'login' => ['max' => 5, 'decay' => 300],
    'login-ip' => ['max' => 20, 'decay' => 300],

    // Failed 2FA challenge codes (authenticator or recovery) per pending
    // user, and per IP. Cleared on success.
    'two-factor' => ['max' => 5, 'decay' => 300],
    'two-factor-ip' => ['max' => 20, 'decay' => 300],

    // New accounts per IP — each one sends a verification SMS/email.
    'register' => ['max' => 5, 'decay' => 3600],

    // Password-reset code requests per IP, across all recipients (each
    // recipient is separately limited by the OTP resend rules).
    'password-reset-request' => ['max' => 10, 'decay' => 3600],

    // Password-reset submissions per IP.
    'password-reset' => ['max' => 10, 'decay' => 600],

    // Wrong current-password attempts per user (change password).
    'current-password' => ['max' => 5, 'decay' => 300],

    // Wrong codes while confirming authenticator setup, per user.
    'two-factor-setup' => ['max' => 5, 'decay' => 300],

    // Email-change requests + resends per user — each mails an address the
    // user typed, so this stops it being used to spam someone.
    'email-change' => ['max' => 5, 'decay' => 3600],

    // Phone-number changes per user — each sends an SMS.
    'phone-change' => ['max' => 3, 'decay' => 3600],

    // HTTP routes (named limiters): data-export downloads per user/IP, and
    // emailed one-time links per IP.
    'downloads' => ['max' => 30, 'decay' => 60],
    'links' => ['max' => 10, 'decay' => 60],

];
