<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\FeatureFlagsServiceProvider;

// Atrium's own providers (atrium-php/core, every installed module) are
// registered through package discovery, before the ones listed here.
return [
    AppServiceProvider::class,
    AuthServiceProvider::class,

    // Discovers app/Features flag classes and adds the @flag Blade directive.
    FeatureFlagsServiceProvider::class,
];
