<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    |
    | Served on the "api" subdomain (config/multidomain.php) under /{version},
    | e.g. https://api.example.com/v1/languages — or /api/{version} on the
    | main domain in single-domain mode. Authenticated with Sanctum personal
    | access tokens created from Account → API tokens; the web portals keep
    | their session auth untouched.
    |
    */

    'version' => 'v1',

    // Requests per minute, per token's user (or per IP when unauthenticated).
    'rate_limit' => (int) env('API_RATE_LIMIT', 60),

    // Largest page size a client may ask for with ?per_page=.
    'max_per_page' => 100,

    // Token abilities offered when creating a token. Routes require one
    // with the `ability:` middleware; the user's own permissions still apply.
    'abilities' => [
        'read' => 'Read data',
        'write' => 'Create, update and delete data',
    ],

    // Expiry choices (days) on the token form; null = never expires.
    'token_expiry_days' => [30, 90, 365, null],

];
