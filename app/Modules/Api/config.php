<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    |
    | Served on the "api" subdomain (config/multidomain.php) under /{version},
    | e.g. https://api.example.com/v1/languages — or /api/{version} on the
    | main domain in single-domain mode. Sanctum personal access tokens only;
    | the web portals keep their session auth.
    |
    | Who may hold tokens is decided at runtime by the access policy on
    | Backoffice → API Access (Super Admin). The values under 'policy' are
    | only its defaults.
    |
    */

    'version' => 'v1',

    // Requests per minute, per token's user (or per IP when unauthenticated).
    'rate_limit' => (int) env('API_RATE_LIMIT', 60),

    // Largest page size a client may ask for with ?per_page=.
    'max_per_page' => 100,

    // Abilities core offers. Modules add theirs with
    // Module::contribute('api.abilities', [['ability' => 'x:read', 'description' => '…']]).
    'abilities' => [
        'profile:read' => 'Read your own profile',
    ],

    // Expiry choices (days) on the token forms; null = never expires.
    'token_expiry_days' => [7, 30, 90, 365, null],

    'policy' => [
        // Who may create their own tokens on Account → API tokens:
        //   none     — nobody; only admins issue tokens (Backoffice → API Access)
        //   staff    — backoffice staff
        //   everyone — every signed-in user
        //   roles    — users with one of 'roles' (role slugs)
        'self_service' => 'none',
        'roles' => [],

        // Abilities self-service tokens may have (admins may issue any).
        'abilities' => ['profile:read'],

        // Longest self-service token lifetime in days (null = no limit),
        // and how many tokens one user may hold.
        'max_days' => 90,
        'max_tokens' => 5,
    ],

];
