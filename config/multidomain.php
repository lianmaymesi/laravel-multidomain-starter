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
];
