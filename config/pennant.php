<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Pennant Store
    |--------------------------------------------------------------------------
    |
    | Here you will specify the default store that Pennant should use when
    | storing and resolving feature flag values. Pennant ships with the
    | ability to store flag values in an in-memory array or database.
    |
    | Supported: "array", "database"
    |
    */

    'default' => env('PENNANT_STORE', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Pennant Stores
    |--------------------------------------------------------------------------
    |
    | Here you may configure each of the stores that should be available to
    | Pennant. These stores shall be used to store resolved feature flag
    | values - you may configure as many as your application requires.
    |
    */

    'stores' => [

        'array' => [
            'driver' => 'array',
        ],

        'database' => [
            'driver' => 'database',
            'connection' => null,
            'table' => 'features',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Kill Switch
    |--------------------------------------------------------------------------
    |
    | Flag names forced off for every portal and user, whatever is stored —
    | e.g. PENNANT_KILLED=hello-world-advanced,whats-new-card. Checked by
    | FeatureFlag::before(), so it also covers portals/users not decided yet
    | (which "All off" on the Feature Flags page does not). Nothing is
    | written: remove the name and the stored values apply again.
    |
    */

    'killed' => array_values(array_filter(array_map('trim', explode(',', (string) env('PENNANT_KILLED', ''))))),
];
