<?php

use Illuminate\Support\Facades\File;

afterEach(function () {
    File::delete([
        app_path('Features/NewCurrencyPicker.php'),
        app_path('Features/BetaReports.php'),
    ]);
});

it('creates a per-portal flag class', function () {
    $this->artisan('make:flag', ['name' => 'new currency picker'])->assertSuccessful();

    $flag = File::get(app_path('Features/NewCurrencyPicker.php'));

    expect($flag)->toContain('class NewCurrencyPicker extends PortalFlag')
        ->and($flag)->toContain("public string \$name = 'new-currency-picker';")
        ->and($flag)->toContain("return 'New Currency Picker';")
        ->and($flag)->not->toContain('{{');
});

it('creates a per-user flag class with --user', function () {
    $this->artisan('make:flag', ['name' => 'BetaReports', '--user' => true])->assertSuccessful();

    expect(File::get(app_path('Features/BetaReports.php')))
        ->toContain('class BetaReports extends UserFlag')
        ->toContain('protected function initial(?User $user): mixed');
});

it('refuses a flag that already exists and invalid names', function () {
    $this->artisan('make:flag', ['name' => 'WhatsNewCard'])->assertFailed();
    $this->artisan('make:flag', ['name' => '9lives'])->assertFailed();
});
