<?php

use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Output\NullOutput;

use function Termwind\renderUsing;

uses(RefreshDatabase::class);

beforeEach(function () {
    // The command renders its summary via Termwind\render(), which writes
    // straight to a real ConsoleOutput by default — bypassing Laravel's
    // command-output buffering and printing to the terminal during `test`.
    renderUsing(new NullOutput);
});

afterEach(function () {
    renderUsing(null);

    File::delete([
        resource_path('css/blog.css'),
        resource_path('js/blog.js'),
        resource_path('views/layouts/blog.blade.php'),
        base_path('routes/blog.php'),
    ]);
    File::deleteDirectory(resource_path('views/pages/blog'));
    File::deleteDirectory(resource_path('views/errors/blog'));
    Role::where('slug', 'blog')->delete();
});

it('scaffolds a new subdomain portal', function () {
    $this->artisan('make:subdomain', ['name' => 'blog'])
        ->expectsQuestion('Who should be able to access this portal?', 'auth')
        ->expectsQuestion('Access level', 'user')
        ->expectsConfirmation('Should visitors be able to sign up for "blog" themselves, from the public register page?', 'no')
        ->expectsQuestion('Error & maintenance page style for this portal?', 'shared')
        ->assertSuccessful();

    expect(resource_path('css/blog.css'))->toBeFile()
        ->and(resource_path('js/blog.js'))->toBeFile()
        ->and(resource_path('views/layouts/blog.blade.php'))->toBeFile()
        ->and(base_path('routes/blog.php'))->toBeFile()
        ->and(resource_path('views/pages/blog/⚡dashboard/dashboard.php'))->toBeFile()
        ->and(resource_path('views/pages/blog/⚡dashboard/dashboard.blade.php'))->toBeFile();

    expect(File::get(base_path('routes/blog.php')))->toContain('pages::blog.dashboard')
        ->and(Role::where('slug', 'blog')->where('guard_name', 'web')->exists())->toBeTrue()
        ->and(Role::where('slug', 'blog')->value('name'))->toBe('Blog');

    // The registration guard is always baked into the dashboard stub —
    // visibility is decided at runtime by the (unpopulated) config array.
    expect(File::get(resource_path('views/pages/blog/⚡dashboard/dashboard.blade.php')))
        ->toContain("array_key_exists('blog', config('multidomain.registerable_portals'");
});

it('opts a subdomain into public self-registration', function () {
    $this->artisan('make:subdomain', ['name' => 'blog'])
        ->expectsQuestion('Who should be able to access this portal?', 'auth')
        ->expectsQuestion('Access level', 'user')
        ->expectsConfirmation('Should visitors be able to sign up for "blog" themselves, from the public register page?', 'yes')
        ->expectsQuestion('What should this portal be called on the sign-up form?', "Blog & Vlog Writer's Club")
        ->expectsQuestion('Error & maintenance page style for this portal?', 'shared')
        ->assertSuccessful();

    expect(resource_path('views/pages/blog/⚡dashboard/dashboard.blade.php'))->toBeFile();
});

it('scaffolds its own error page style when chosen', function () {
    $this->artisan('make:subdomain', ['name' => 'blog'])
        ->expectsQuestion('Who should be able to access this portal?', 'auth')
        ->expectsQuestion('Access level', 'user')
        ->expectsConfirmation('Should visitors be able to sign up for "blog" themselves, from the public register page?', 'no')
        ->expectsQuestion('Error & maintenance page style for this portal?', 'own')
        ->assertSuccessful();

    expect(resource_path('views/errors/blog/page.blade.php'))->toBeFile();
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

it('rejects scaffolding while single-domain mode is active', function () {
    config(['multidomain.single_domain' => true]);

    $this->artisan('make:subdomain', ['name' => 'blog'])
        ->assertFailed();

    expect(resource_path('css/blog.css'))->not->toBeFile();
});
