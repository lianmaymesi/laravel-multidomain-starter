<?php

namespace App\Support\Modules;

use Illuminate\Contracts\Config\Repository;

class ModuleManager
{
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
}
