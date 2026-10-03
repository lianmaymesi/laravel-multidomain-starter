<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/*
 * make:module writes into the app (config/modules.php, .env.example,
 * app/Modules/…, tests/…). Doing that in the real checkout raced with other
 * parallel test workers reading those files — so each test points the app at
 * a throwaway sandbox holding just what the command touches.
 */
beforeEach(function () {
    $this->sandbox = sys_get_temp_dir().DIRECTORY_SEPARATOR.'make-module-'.Str::random(8);

    File::copyDirectory(base_path('stubs'), "{$this->sandbox}/stubs");
    File::ensureDirectoryExists("{$this->sandbox}/config");
    File::copy(config_path('modules.php'), "{$this->sandbox}/config/modules.php");
    File::copy(base_path('.env.example'), "{$this->sandbox}/.env.example");
    File::copy(base_path('phpstan.neon'), "{$this->sandbox}/phpstan.neon");
    File::ensureDirectoryExists("{$this->sandbox}/app/Modules/Maintenance");
    File::ensureDirectoryExists("{$this->sandbox}/tests/Feature/Modules");

    $this->app->setBasePath($this->sandbox);
});

afterEach(function () {
    File::deleteDirectory($this->sandbox);
});

it('scaffolds a module with provider, routes, page, migrations dir and test', function () {
    $this->artisan('make:module', ['name' => 'Invoices'])->assertSuccessful();

    $provider = File::get(app_path('Modules/Invoices/InvoicesServiceProvider.php'));

    expect($provider)->toContain('namespace App\Modules\Invoices;')
        ->and($provider)->toContain("return 'invoices';")
        ->and($provider)->toContain("'invoices.view'")
        ->and($provider)->not->toContain('{{');

    expect(File::exists(app_path('Modules/Invoices/routes/backoffice.php')))->toBeTrue()
        ->and(File::exists(app_path('Modules/Invoices/resources/views/livewire/⚡index/index.php')))->toBeTrue()
        ->and(File::exists(app_path('Modules/Invoices/resources/views/livewire/⚡index/index.blade.php')))->toBeTrue()
        ->and(File::isDirectory(app_path('Modules/Invoices/database/migrations')))->toBeTrue()
        ->and(File::exists(base_path('tests/Feature/Modules/InvoicesModuleTest.php')))->toBeTrue();
});

it('writes into the sandbox, never the real checkout', function () {
    $this->artisan('make:module', ['name' => 'Invoices'])->assertSuccessful();

    expect(app_path())->toStartWith($this->sandbox)
        ->and(is_dir(dirname(__DIR__, 3).'/app/Modules/Invoices'))->toBeFalse();
});

it('registers the on/off toggle in config/modules.php and .env.example', function () {
    $this->artisan('make:module', ['name' => 'Invoices'])->assertSuccessful();

    expect(File::get(config_path('modules.php')))
        ->toContain("'invoices' => (bool) env('MODULE_INVOICES', true),")
        ->and(File::get(base_path('.env.example')))->toContain('# MODULE_INVOICES=true');

    // Still valid PHP that returns the array, with the existing toggle intact.
    $config = require config_path('modules.php');

    expect($config)->toHaveKeys(['maintenance', 'invoices']);
});

it('turns multi-word names into a studly folder and kebab toggle', function () {
    $this->artisan('make:module', ['name' => 'invoice reports'])->assertSuccessful();

    expect(File::exists(app_path('Modules/InvoiceReports/InvoiceReportsServiceProvider.php')))->toBeTrue()
        ->and(File::get(config_path('modules.php')))->toContain("'invoice-reports' => (bool) env('MODULE_INVOICE_REPORTS', true),");
});

it('refuses a module that already exists', function () {
    $this->artisan('make:module', ['name' => 'Maintenance'])->assertFailed();
});

it('rejects invalid names', function () {
    $this->artisan('make:module', ['name' => '9lives'])->assertFailed();
});

it('registers the module migrations folder for static analysis', function () {
    $this->artisan('make:module', ['name' => 'Invoices'])->assertSuccessful();

    $neon = File::get(base_path('phpstan.neon'));

    expect($neon)->toContain("        - app/Modules/Maintenance/database/migrations\n        - app/Modules/Invoices/database/migrations\n");

    // Running again (another module) keeps the list tidy and doesn't duplicate.
    $this->artisan('make:module', ['name' => 'invoice reports'])->assertSuccessful();

    expect(substr_count(File::get(base_path('phpstan.neon')), 'app/Modules/Invoices/database/migrations'))->toBe(1)
        ->and(File::get(base_path('phpstan.neon')))->toContain('app/Modules/InvoiceReports/database/migrations');
});
