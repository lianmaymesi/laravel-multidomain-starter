<?php

namespace App\Support\Modules;

use Illuminate\Contracts\Config\Repository;

class ModuleManager
{
    /** @var array<string, array<int, mixed>> */
    private array $contributions = [];

    /** @var array<string, array{label: string, description: string, icon: string}> */
    private array $meta = [];

    /** @var array<string, bool> config/modules.php values before any runtime override */
    private array $defaults = [];

    /** @var array<string, bool> */
    private array $overrides = [];

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

    /**
     * Layer runtime overrides (the backoffice Modules page) over the
     * config/modules.php defaults. Must run before any module provider
     * registers — everything downstream just reads config('modules.*').
     * Overrides for modules that no longer exist are ignored.
     *
     * @param  array<string, bool>  $overrides  module => enabled
     */
    public function applyOverrides(array $overrides): void
    {
        $this->defaults = array_map('boolval', $this->config->get('modules', []));
        $this->overrides = array_intersect_key(array_map('boolval', $overrides), $this->defaults);

        $this->config->set('modules', [...$this->defaults, ...$this->overrides]);
    }

    /** The config/modules.php value, ignoring any runtime override. */
    public function defaultEnabled(string $module): bool
    {
        return $this->defaults[$module] ?? $this->enabled($module);
    }

    public function isOverridden(string $module): bool
    {
        return array_key_exists($module, $this->overrides);
    }

    /**
     * Label/description/icon a module's provider declared, for the Modules page.
     *
     * @param  array{label?: string, description?: string, icon?: string}  $meta
     */
    public function describe(string $module, array $meta): void
    {
        $this->meta[$module] = [
            'label' => $meta['label'] ?? str($module)->headline()->toString(),
            'description' => $meta['description'] ?? '',
            'icon' => $meta['icon'] ?? 'puzzle-piece',
        ];
    }

    /**
     * @return array{key: string, label: string, description: string, icon: string, enabled: bool, default: bool, overridden: bool}
     */
    public function info(string $module): array
    {
        return [
            'key' => $module,
            ...($this->meta[$module] ?? ['label' => str($module)->headline()->toString(), 'description' => '', 'icon' => 'puzzle-piece']),
            'enabled' => $this->enabled($module),
            'default' => $this->defaultEnabled($module),
            'overridden' => $this->isOverridden($module),
        ];
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
