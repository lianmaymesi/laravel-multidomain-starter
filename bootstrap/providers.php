<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\ModulesServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,

    // Discovers and registers every app/Modules/*/*ServiceProvider.
    ModulesServiceProvider::class,
];
