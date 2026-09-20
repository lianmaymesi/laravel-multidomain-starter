<?php

namespace App\Support\Modules;

use Illuminate\Support\ServiceProvider;
use ReflectionClass;

/**
 * Base class for a module's service provider. Providers are registered
 * unconditionally in bootstrap/providers.php — the on/off guard lives here,
 * so a disabled module contributes nothing to the container, router or views.
 *
 * Migrations are the one exception: they always load, so the schema exists
 * even while the module is off and enabling it later needs no extra step.
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

        if (! $this->enabled()) {
            return;
        }

        $this->bootModule();
    }

    /** Bind services. Only runs while the module is enabled. */
    protected function registerModule(): void {}

    /** Middleware, Livewire namespaces, listeners. Only runs while enabled. */
    protected function bootModule(): void {}

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
