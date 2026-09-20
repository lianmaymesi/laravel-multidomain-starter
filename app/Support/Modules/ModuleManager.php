<?php

namespace App\Support\Modules;

use Illuminate\Contracts\Config\Repository;

class ModuleManager
{
    /** @var array<string, array<int, mixed>> */
    private array $contributions = [];

    public function __construct(private Repository $config) {}

    /**
     * Whether a module is switched on. Unknown modules count as disabled, so
     * a typo can never accidentally enable (or crash) anything.
     */
    public function enabled(string $module): bool
    {
        return (bool) $this->config->get("modules.{$module}", false);
    }

    /** @return array<int, string> */
    public function names(): array
    {
        return array_keys($this->config->get('modules', []));
    }

    /** @return array<int, string> */
    public function enabledNames(): array
    {
        return array_values(array_filter($this->names(), fn (string $module) => $this->enabled($module)));
    }

    public function path(string $module, string $path = ''): string
    {
        $base = app_path('Modules/'.str($module)->studly());

        return $path === '' ? $base : $base.DIRECTORY_SEPARATOR.ltrim($path, '/\\');
    }

    /**
     * Include every enabled module's routes/{portal}.php. Call this from
     * inside a portal's route file (routes/backoffice.php, ...) so module
     * routes inherit that portal's domain, prefix, name and middleware group
     * exactly like the core routes next to them.
     */
    public function routes(string $portal): void
    {
        foreach ($this->enabledNames() as $module) {
            $file = $this->path($module, "routes/{$portal}.php");

            if (is_file($file)) {
                include $file;
            }
        }
    }

    /**
     * Named extension points, so a module can plug into core UI or
     * bootstrapping without core ever referencing the module:
     *
     *   backoffice.nav             sidebar links   [label, route, icon, permission, order, mobile]
     *   backoffice.settings.cards  Settings page   [component, permission]
     *   permissions                permission names to seed
     *   database.seeders           seeder classes DatabaseSeeder should call
     *
     * A disabled module contributes nothing, so core just loops over
     * whatever is there — no Module::enabled() checks needed at the call site.
     *
     * @param  array<int, mixed>  $items
     */
    public function contribute(string $point, array $items): void
    {
        $this->contributions[$point] = [...($this->contributions[$point] ?? []), ...array_values($items)];
    }

    /** @return array<int, mixed> */
    public function contributions(string $point): array
    {
        return $this->contributions[$point] ?? [];
    }
}
