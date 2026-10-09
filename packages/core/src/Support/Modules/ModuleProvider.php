<?php

namespace Atrium\Core\Support\Modules;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
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
        Module::describe($this->module(), [
            'label' => $this->label(),
            'description' => $this->description(),
            'icon' => $this->icon(),
        ]);

        Module::locate($this->module(), $this->modulePath());

        if (! $this->enabled()) {
            $this->registerDisabled();

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

    /** Name shown on the backoffice Modules page. Defaults to the module key, headlined. */
    protected function label(): string
    {
        return str($this->module())->headline()->toString();
    }

    /** One line shown under the name on the Modules page. */
    protected function description(): string
    {
        return '';
    }

    /** Flux icon name for the Modules page. */
    protected function icon(): string
    {
        return 'puzzle-piece';
    }

    /**
     * Runs INSTEAD of registerModule() while the module is off — for switching
     * off behaviour that lives outside the module (e.g. a package or model
     * trait in core that would otherwise keep acting on its own).
     */
    protected function registerDisabled(): void {}

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

    /**
     * Add middleware to a group (e.g. 'web') from bootModule(). Goes through
     * the HTTP kernel, not the router: the kernel re-copies its groups onto
     * the router whenever its middleware changes (Sanctum's provider does
     * that while booting), wiping anything pushed onto the router directly.
     */
    protected function appendMiddlewareToGroup(string $group, string $middleware): void
    {
        $this->app->make(HttpKernel::class)->appendMiddlewareToGroup($group, $middleware);
    }

    protected function enabled(): bool
    {
        return Module::enabled($this->module());
    }

    /**
     * The module's root folder: where the provider lives (app/Modules/X), or
     * the package root when the provider sits in a package's src/.
     */
    protected function modulePath(string $path = ''): string
    {
        $base = dirname((new ReflectionClass($this))->getFileName());

        if (basename($base) === 'src') {
            $base = dirname($base);
        }

        return $path === '' ? $base : $base.DIRECTORY_SEPARATOR.$path;
    }
}
