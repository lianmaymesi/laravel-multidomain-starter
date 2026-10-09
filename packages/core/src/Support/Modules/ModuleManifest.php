<?php

namespace Atrium\Core\Support\Modules;

/**
 * Modules shipped as Composer packages. Any installed package declaring
 *
 *   "type": "atrium-module",
 *   "extra": {"atrium": {"module": "invoices", "provider": "Acme\\Invoices\\InvoicesServiceProvider", "enabled": true}}
 *
 * is a module — first-party (atrium-php/*) and third-party alike, no vendor
 * is special-cased. `module` defaults to the package name after the slash,
 * `enabled` (the default before config/modules.php or the Modules page say
 * otherwise) defaults to true.
 *
 * Read from vendor/composer/installed.json and cached as a PHP array in
 * bootstrap/cache/atrium-modules.php, rebuilt whenever Composer has
 * installed, updated or removed something since.
 */
class ModuleManifest
{
    /** @var array<string, array{provider: string, enabled: bool, path: string, package: string}>|null */
    private ?array $modules = null;

    public function __construct(
        private string $vendorPath,
        private string $manifestPath,
    ) {}

    /** @return array<string, array{provider: string, enabled: bool, path: string, package: string}> module => entry */
    public function modules(): array
    {
        if ($this->modules !== null) {
            return $this->modules;
        }

        if ($this->isStale()) {
            $this->build();
        }

        return $this->modules = is_file($this->manifestPath) ? require $this->manifestPath : [];
    }

    public function build(): void
    {
        $modules = [];

        foreach ($this->installedPackages() as $package) {
            $atrium = $package['extra']['atrium'] ?? null;

            if (($package['type'] ?? null) !== 'atrium-module' || ! isset($atrium['provider'])) {
                continue;
            }

            $module = $atrium['module'] ?? substr((string) strrchr($package['name'], '/'), 1);

            $modules[$module] = [
                'provider' => $atrium['provider'],
                'enabled' => (bool) ($atrium['enabled'] ?? true),
                'path' => $this->installPath($package),
                'package' => $package['name'],
            ];
        }

        ksort($modules);

        if (! is_dir($directory = dirname($this->manifestPath))) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($this->manifestPath, '<?php return '.var_export($modules, true).';'.PHP_EOL, LOCK_EX);

        $this->modules = $modules;
    }

    private function isStale(): bool
    {
        if (! is_file($this->manifestPath)) {
            return true;
        }

        $installed = $this->installedJsonPath();

        return is_file($installed) && filemtime($installed) > filemtime($this->manifestPath);
    }

    /** @return array<int, array<string, mixed>> */
    private function installedPackages(): array
    {
        if (! is_file($path = $this->installedJsonPath())) {
            return [];
        }

        $installed = json_decode((string) file_get_contents($path), true) ?: [];

        // Composer 2 wraps the list in {"packages": [...]}; Composer 1 didn't.
        return $installed['packages'] ?? $installed;
    }

    /** @param  array<string, mixed>  $package */
    private function installPath(array $package): string
    {
        $path = isset($package['install-path'])
            ? $this->vendorPath.'/composer/'.$package['install-path']
            : $this->vendorPath.'/'.$package['name'];

        return realpath($path) ?: $path;
    }

    private function installedJsonPath(): string
    {
        return $this->vendorPath.'/composer/installed.json';
    }
}
