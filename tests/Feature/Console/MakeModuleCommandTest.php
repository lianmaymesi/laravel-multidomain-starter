<?php

use Illuminate\Support\Facades\File;

beforeEach(function () {
    // The command edits these two real files — snapshot so afterEach can restore them.
    $this->modulesConfig = File::get(config_path('modules.php'));
    $this->envExample = File::get(base_path('.env.example'));
});

afterEach(function () {
    File::put(config_path('modules.php'), $this->modulesConfig);
    File::put(base_path('.env.example'), $this->envExample);

    File::deleteDirectory(app_path('Modules/Invoices'));
    File::deleteDirectory(app_path('Modules/InvoiceReports'));
    File::delete([
        base_path('tests/Feature/Modules/InvoicesModuleTest.php'),
        base_path('tests/Feature/Modules/InvoiceReportsModuleTest.php'),
    ]);
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
