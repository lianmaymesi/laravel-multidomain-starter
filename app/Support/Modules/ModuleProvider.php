<?php

namespace App\Support\Modules;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use ReflectionClass;

/**
 * Base class for a module's service provider. Providers are discovered
 * automatically (see ModulesServiceProvider) — the on/off guard lives here,
 * so a disabled module contributes nothing to the container, router or views.
 *
 * Some things are deliberately NOT gated, so the schema and data survive a
 * disable/enable cycle and enabling later needs no extra step: migrations,
 * and the permission/seeder lists.
 */
abstract class ModuleProvider extends ServiceProvider
{
    /** Config key under modules.php, e.g. "maintenance". */
    abstract protected function module(): string;

    final public function register(): void
    {
        if (! $this->enabled()) {
            return;
        }

        if (is_file($config = $this->modulePath('config.php'))) {
            $this->mergeConfigFrom($config, $this->module());
        }

        $this->registerModule();
    }

    final public function boot(): void
    {
        $this->loadMigrationsFrom($this->modulePath('database/migrations'));

        Module::contribute('permissions', $this->permissions());
        Module::contribute('permissions.super-admin-only', $this->superAdminOnlyPermissions());
        Module::contribute('database.seeders', $this->seeders());

        if (! $this->enabled()) {
            return;
        }

        $this->bootModule();

        if ($this->app->runningInConsole()) {
            $this->app->booted(fn () => $this->schedule($this->app->make(Schedule::class)));
        }
    }

    /** Bind services. Only runs while the module is enabled. */
    protected function registerModule(): void {}

    /** Middleware, Livewire namespaces, listeners, nav items. Only runs while enabled. */
    protected function bootModule(): void {}

    /** Scheduled jobs/commands. Only runs while enabled. */
    protected function schedule(Schedule $schedule): void {}

    /**
     * Permission names this module owns, e.g. ['billing.view'].
     * Always seeded, even while disabled, so roles keep them across a toggle.
     *
     * @return array<int, string>
     */
    protected function permissions(): array
    {
        return [];
    }

    /**
     * Subset of permissions() that Admin is never granted — reserved for
     * Super Admin.
     *
     * @return array<int, string>
     */
    protected function superAdminOnlyPermissions(): array
    {
        return [];
    }

    /**
     * Seeder classes DatabaseSeeder should run for this module.
     *
     * @return array<int, class-string>
     */
    protected function seeders(): array
    {
        return [];
    }

    protected function enabled(): bool
    {
        return Module::enabled($this->module());
    }

    protected function modulePath(string $path = ''): string
    {
        $base = dirname((new ReflectionClass($this))->getFileName());

        return $path === '' ? $base : $base.DIRECTORY_SEPARATOR.$path;
    }
}
