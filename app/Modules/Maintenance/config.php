<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Global Maintenance Switch
    |--------------------------------------------------------------------------
    |
    | Absolute kill switch — when true, every portal (backoffice included)
    | shows the maintenance page, regardless of the per-portal DB toggle
    | below. Meant for env-driven deploys, no CLI access required.
    |
    */

    'global' => (bool) env('APP_MAINTENANCE', false),

    /*
    |--------------------------------------------------------------------------
    | Portals Exempt From The Per-Portal Toggle
    |--------------------------------------------------------------------------
    |
    | "backoffice" is exempt because staff always need a way in to flip
    | things back. "account" and "auth" are exempt because they're
    | supportive portals for app/backoffice/any user-facing portal, not
    | independent destinations of their own — taking them down in
    | isolation would just strand users mid-login or mid-account-task on
    | whichever portal sent them there. "api" is exempt because it's not a
    | UI portal at all; there's nothing for a maintenance page to render.
    | The global switch above overrides every exemption here.
    |
    */

    'exempt_portals' => ['backoffice', 'account', 'auth', 'api'],

];
