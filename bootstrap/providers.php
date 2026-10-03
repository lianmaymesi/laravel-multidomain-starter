<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\FeatureFlagsServiceProvider;
use App\Providers\ModulesServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,

    // Discovers app/Features flag classes and adds the @flag Blade directive.
    FeatureFlagsServiceProvider::class,

    // Discovers and registers every app/Modules/*/*ServiceProvider.
    ModulesServiceProvider::class,
];
