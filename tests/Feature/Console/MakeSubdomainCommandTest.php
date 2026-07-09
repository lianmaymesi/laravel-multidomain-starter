<?php

use Illuminate\Support\Facades\File;

afterEach(function () {
    File::delete([
        resource_path('css/blog.css'),
        resource_path('js/blog.js'),
        resource_path('views/layouts/blog.blade.php'),
        base_path('routes/blog.php'),
    ]);
    File::deleteDirectory(resource_path('views/pages/blog'));
});

it('scaffolds a new subdomain portal', function () {
    $this->artisan('make:subdomain', ['name' => 'blog'])
        ->assertSuccessful();

    expect(resource_path('css/blog.css'))->toBeFile()
        ->and(resource_path('js/blog.js'))->toBeFile()
        ->and(resource_path('views/layouts/blog.blade.php'))->toBeFile()
        ->and(base_path('routes/blog.php'))->toBeFile()
        ->and(resource_path('views/pages/blog/⚡dashboard/dashboard.php'))->toBeFile()
        ->and(resource_path('views/pages/blog/⚡dashboard/dashboard.blade.php'))->toBeFile();

    expect(File::get(base_path('routes/blog.php')))->toContain('pages::blog.dashboard');
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
