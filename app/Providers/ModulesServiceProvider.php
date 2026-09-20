<?php

namespace App\Providers;

use App\Support\Modules\Module;
use App\Support\Modules\ModuleManager;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * Core module plumbing — the registry and the Blade guard. Individual module
 * providers (app/Modules/{Name}/{Name}ServiceProvider) are listed separately
 * in bootstrap/providers.php.
 */
class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleManager::class);
    }

    public function boot(): void
    {
        // @module('maintenance') ... @endmodule
        Blade::if('module', fn (string $module) => Module::enabled($module));
    }
}
