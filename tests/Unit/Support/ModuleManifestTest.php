<?php

use Atrium\Core\Support\Modules\ModuleManifest;
use Illuminate\Filesystem\Filesystem;

beforeEach(function () {
    $this->root = sys_get_temp_dir().'/atrium-manifest-'.bin2hex(random_bytes(4));
    mkdir($this->root.'/vendor/composer', 0755, true);
    mkdir($this->root.'/vendor/acme/invoices', 0755, true);

    $this->installed = function (array $packages, ?int $mtime = null) {
        $path = $this->root.'/vendor/composer/installed.json';
        file_put_contents($path, json_encode(['packages' => $packages]));

        if ($mtime !== null) {
            touch($path, $mtime);
        }
    };

    $this->manifest = fn () => new ModuleManifest($this->root.'/vendor', $this->root.'/bootstrap/cache/atrium-modules.php');
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->root);
});

it('finds atrium-module packages from any vendor', function () {
    ($this->installed)([
        ['name' => 'acme/invoices', 'type' => 'atrium-module', 'install-path' => '../acme/invoices',
            'extra' => ['atrium' => ['provider' => 'Acme\Invoices\InvoicesServiceProvider', 'enabled' => false]]],
        ['name' => 'atrium-php/media', 'type' => 'atrium-module',
            'extra' => ['atrium' => ['module' => 'media', 'provider' => 'Atrium\Media\MediaServiceProvider']]],
    ]);

    $modules = ($this->manifest)()->modules();

    expect(array_keys($modules))->toBe(['invoices', 'media'])
        ->and($modules['invoices']['provider'])->toBe('Acme\Invoices\InvoicesServiceProvider')
        ->and($modules['invoices']['enabled'])->toBeFalse()
        ->and($modules['invoices']['package'])->toBe('acme/invoices')
        ->and($modules['invoices']['path'])->toBe(realpath($this->root.'/vendor/acme/invoices'))
        ->and($modules['media']['enabled'])->toBeTrue();
});

it('ignores other package types and modules without a provider', function () {
    ($this->installed)([
        ['name' => 'laravel/framework', 'type' => 'library', 'extra' => ['atrium' => ['provider' => 'X']]],
        ['name' => 'acme/broken', 'type' => 'atrium-module', 'extra' => []],
    ]);

    expect(($this->manifest)()->modules())->toBe([]);
});

it('caches the manifest and rebuilds it after composer changes the installed packages', function () {
    ($this->installed)([], time() - 60);

    expect(($this->manifest)()->modules())->toBe([])
        ->and(file_exists($this->root.'/bootstrap/cache/atrium-modules.php'))->toBeTrue();

    ($this->installed)([
        ['name' => 'acme/invoices', 'type' => 'atrium-module', 'extra' => ['atrium' => ['provider' => 'Acme\Invoices\InvoicesServiceProvider']]],
    ], time() + 60);

    expect(array_keys(($this->manifest)()->modules()))->toBe(['invoices']);
});

it('works with no vendor/composer/installed.json at all', function () {
    expect(($this->manifest)()->modules())->toBe([]);
});
