<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

afterEach(function () {
    File::delete([
        resource_path('css/blog.css'),
        resource_path('js/blog.js'),
        resource_path('views/layouts/blog.blade.php'),
        base_path('routes/blog.php'),
    ]);
    File::deleteDirectory(resource_path('views/pages/blog'));
    Role::where('name', 'blog')->delete();
});

it('scaffolds a new subdomain portal', function () {
    $this->artisan('make:subdomain', ['name' => 'blog'])
        ->expectsQuestion('Who should be able to access this portal?', 'auth')
        ->expectsQuestion('Access level', 'user')
        ->assertSuccessful();

    expect(resource_path('css/blog.css'))->toBeFile()
        ->and(resource_path('js/blog.js'))->toBeFile()
        ->and(resource_path('views/layouts/blog.blade.php'))->toBeFile()
        ->and(base_path('routes/blog.php'))->toBeFile()
        ->and(resource_path('views/pages/blog/⚡dashboard/dashboard.php'))->toBeFile()
        ->and(resource_path('views/pages/blog/⚡dashboard/dashboard.blade.php'))->toBeFile();

    expect(File::get(base_path('routes/blog.php')))->toContain('pages::blog.dashboard')
        ->and(Role::where('name', 'blog')->where('guard_name', 'web')->exists())->toBeTrue();
});

it('rejects an invalid subdomain name', function () {
    $this->artisan('make:subdomain', ['name' => 'Blog Name!'])
        ->assertFailed();

    expect(resource_path('css/blog.css'))->not->toBeFile();
});

it('rejects a subdomain name that already exists', function () {
    $this->artisan('make:subdomain', ['name' => 'app'])
        ->assertFailed();
});
