<?php

namespace App\Providers;

use App\Support\Modules\Module;
use App\Support\Modules\ModuleManager;
use App\Support\Modules\ModuleProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * Core module plumbing: the registry, the Blade guard, and discovery of every
 * app/Modules/{Name}/{Name}ServiceProvider.php — dropping a module folder in
 * (or running `php artisan make:module`) is all it takes to register one.
 */
class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleManager::class);

        foreach (glob(app_path('Modules/*/*ServiceProvider.php')) ?: [] as $file) {
            $class = 'App\\Modules\\'.basename(dirname($file)).'\\'.basename($file, '.php');

            if (is_subclass_of($class, ModuleProvider::class)) {
                $this->app->register($class);
            }
        }
    }

    public function boot(): void
    {
        // @module('maintenance') ... @endmodule
        Blade::if('module', fn (string $module) => Module::enabled($module));
    }
}
