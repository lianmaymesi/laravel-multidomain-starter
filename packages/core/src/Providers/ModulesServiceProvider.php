<?php

namespace Atrium\Core\Providers;

use Atrium\Core\Models\ModuleSetting;
use Atrium\Core\Support\Modules\Module;
use Atrium\Core\Support\Modules\ModuleManager;
use Atrium\Core\Support\Modules\ModuleManifest;
use Atrium\Core\Support\Modules\ModuleProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

/**
 * Core module plumbing: the registry, the Blade guard, and discovery of every
 * module. Two sources, same ModuleProvider contract:
 *
 *  - packages: any installed Composer package with "type": "atrium-module"
 *    (first-party atrium-php/* and third-party alike — see ModuleManifest)
 *  - project modules: app/Modules/{Name}/{Name}ServiceProvider.php, so
 *    dropping a folder in (or `php artisan make:module`) registers one.
 *
 * Registered through package discovery, which runs before the project's own
 * providers in bootstrap/providers.php.
 */
class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ModuleManager::class);
        $this->app->singleton(ModuleManifest::class, fn () => new ModuleManifest(
            $this->app->basePath('vendor'),
            $this->app->bootstrapPath('cache/atrium-modules.php'),
        ));

        $packages = $this->app->make(ModuleManifest::class)->modules();

        // A package module the project's config/modules.php doesn't list
        // still gets a toggle, defaulting to what the package declares.
        foreach ($packages as $module => $package) {
            if (! $this->app['config']->has("modules.{$module}")) {
                $this->app['config']->set("modules.{$module}", $package['enabled']);
            }
        }

        // Runtime overrides from the backoffice Modules page, layered over
        // config/modules.php. Has to happen here, before any module registers.
        $this->app->make(ModuleManager::class)->applyOverrides(ModuleSetting::overrides());

        foreach ($packages as $package) {
            $this->registerModule($package['provider']);
        }

        foreach (glob(app_path('Modules/*/*ServiceProvider.php')) ?: [] as $file) {
            $this->registerModule('App\\Modules\\'.basename(dirname($file)).'\\'.basename($file, '.php'));
        }
    }

    public function boot(): void
    {
        // @module('maintenance') ... @endmodule
        Blade::if('module', fn (string $module) => Module::enabled($module));
    }

    private function registerModule(string $class): void
    {
        if (is_subclass_of($class, ModuleProvider::class)) {
            $this->app->register($class);
        }
    }
}
