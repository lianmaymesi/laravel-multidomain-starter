<?php

use App\Modules\Maintenance\MaintenanceServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\ModulesServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    ModulesServiceProvider::class,

    // Feature modules — always registered; each guards itself via
    // config/modules.php (see App\Support\Modules\ModuleProvider).
    MaintenanceServiceProvider::class,
];
