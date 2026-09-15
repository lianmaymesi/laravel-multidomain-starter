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
    | These portals never go into maintenance via the backoffice-controlled
    | PortalSetting toggle — staff always need a way in to flip things back.
    | The global switch above overrides this exemption.
    |
    */

    'exempt_portals' => ['backoffice'],

];
