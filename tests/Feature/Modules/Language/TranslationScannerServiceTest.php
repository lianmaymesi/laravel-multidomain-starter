<?php

use App\Modules\Language\Models\LanguageLine;
use App\Modules\Language\Services\TranslationScannerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// The scanner reads app/ and resources/views — give it a small fixture tree
// instead of the real codebase, so expectations are exact.
beforeEach(function () {
    $this->sandbox = sys_get_temp_dir().DIRECTORY_SEPARATOR.'scanner-'.Str::random(8);

    $files = [
        'app/Support/Thing.php' => "<?php __('From app code'); __(\"It's fine\");",
        'resources/views/pages/landing/home.blade.php' => "{{ __('Welcome') }} {{ __('Shared') }}",
        'resources/views/pages/auth/login.blade.php' => "{{ __('Sign in') }}",
        'resources/views/pages/backoffice/users.blade.php' => "{{ __('Users') }} {{ __('Shared') }}",
        'resources/views/layouts/app.blade.php' => "{{ __('Dashboard') }}",
        'resources/views/components/button.blade.php' => "{{ __('Save') }} {{ __('Escaped \\'quote\\'') }}",
        'resources/views/components/readme.txt' => "__('Not PHP')",
    ];

    foreach ($files as $path => $contents) {
        File::ensureDirectoryExists(dirname("{$this->sandbox}/{$path}"));
        File::put("{$this->sandbox}/{$path}", $contents);
    }

    $this->app->setBasePath($this->sandbox);
});

afterEach(function () {
    File::deleteDirectory($this->sandbox);
});

it('finds every __() string once, tagged with the scope of the first file it appears in', function () {
    $found = app(TranslationScannerService::class)->scan()->pluck('scope', 'key')->all();

    expect($found)->toMatchArray([
        'From app code' => LanguageLine::SCOPE_COMMON,
        "It's fine" => LanguageLine::SCOPE_COMMON,
        'Welcome' => LanguageLine::SCOPE_LANDING,
        'Sign in' => LanguageLine::SCOPE_LANDING,
        'Users' => LanguageLine::SCOPE_PORTAL,
        'Dashboard' => LanguageLine::SCOPE_PORTAL,
        'Save' => LanguageLine::SCOPE_COMMON,
        "Escaped 'quote'" => LanguageLine::SCOPE_COMMON,
    ])->and($found)->toHaveKey('Shared')
        ->and($found)->not->toHaveKey('Not PHP');

    expect(count($found))->toBe(9);
});

it('lists only strings without a translation row as pending', function () {
    LanguageLine::create(['group' => '*', 'key' => 'Welcome', 'scope' => 'landing', 'text' => ['en' => 'Welcome']]);

    $pending = app(TranslationScannerService::class)->pending()->pluck('key');

    expect($pending)->not->toContain('Welcome')
        ->and($pending)->toContain('Users');
});

it('syncs only the scopes the caller may edit, seeding English with the key', function () {
    $created = app(TranslationScannerService::class)->sync([LanguageLine::SCOPE_PORTAL]);

    expect($created)->toBe(LanguageLine::where('scope', 'portal')->count())
        ->and(LanguageLine::where('scope', '!=', 'portal')->exists())->toBeFalse()
        ->and(LanguageLine::where('key', 'Users')->sole()->text)->toBe(['en' => 'Users']);

    // Running again creates nothing new.
    expect(app(TranslationScannerService::class)->sync([LanguageLine::SCOPE_PORTAL]))->toBe(0);
});
