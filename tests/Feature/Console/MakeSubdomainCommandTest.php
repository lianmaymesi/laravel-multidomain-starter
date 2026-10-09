<?php

use Atrium\Core\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Output\NullOutput;

use function Termwind\renderUsing;

uses(RefreshDatabase::class);

/*
 * make:subdomain writes into the app (resources/css, js, views, routes/…).
 * Doing that in the real checkout raced with parallel test workers, so each
 * test points the app at a throwaway sandbox. The database is migrated
 * before this runs, so Role rows still work.
 */
beforeEach(function () {
    // The command renders its summary via Termwind\render(), which writes
    // straight to a real ConsoleOutput by default — bypassing Laravel's
    // command-output buffering and printing to the terminal during `test`.
    renderUsing(new NullOutput);

    $this->sandbox = sys_get_temp_dir().DIRECTORY_SEPARATOR.'make-subdomain-'.Str::random(8);
    File::copyDirectory(base_path('stubs'), "{$this->sandbox}/stubs");

    $this->app->setBasePath($this->sandbox);
});

afterEach(function () {
    renderUsing(null);

    File::deleteDirectory($this->sandbox);
});

function answerSubdomainPrompts($command, string $access = 'auth', ?string $level = 'own-role', string $style = 'shared', ?string $registerAs = null)
{
    $command->expectsQuestion('Who should be able to access this portal?', $access);

    if ($access === 'auth') {
        $command->expectsQuestion('Access level', $level);
    }

    $question = 'Should visitors be able to sign up for "blog" themselves, from the public register page?';

    $registerAs === null
        ? $command->expectsConfirmation($question, 'no')
        : $command->expectsConfirmation($question, 'yes')
            ->expectsQuestion('What should this portal be called on the sign-up form?', $registerAs);

    return $command->expectsQuestion('Error & maintenance page style for this portal?', $style);
}

it('scaffolds the portal files and its own role', function () {
    answerSubdomainPrompts($this->artisan('make:subdomain', ['name' => 'blog']))->assertSuccessful();

    foreach ([
        resource_path('css/blog.css'),
        resource_path('js/blog.js'),
        resource_path('views/layouts/blog.blade.php'),
        base_path('routes/blog.php'),
        resource_path('views/pages/blog/⚡dashboard/dashboard.php'),
        resource_path('views/pages/blog/⚡dashboard/dashboard.blade.php'),
    ] as $file) {
        expect($file)->toBeFile()
            ->and(File::get($file))->not->toContain('{{ name }}');
    }

    expect(File::get(base_path('routes/blog.php')))->toContain('pages::blog.dashboard')
        ->and(File::get(base_path('routes/blog.php')))->toContain("'portal:blog'")
        ->and(Role::where('slug', 'blog')->where('guard_name', 'web')->sole()->name)->toBe('Blog');

    // The registration guard is always baked into the dashboard stub —
    // visibility is decided at runtime by the (unpopulated) config array.
    expect(File::get(resource_path('views/pages/blog/⚡dashboard/dashboard.blade.php')))
        ->toContain("array_key_exists('blog', config('multidomain.registerable_portals'");
});

it('gates the portal with the chosen access level', function (string $access, ?string $level, ?string $expected) {
    answerSubdomainPrompts($this->artisan('make:subdomain', ['name' => 'blog']), $access, $level)->assertSuccessful();

    $routes = File::get(base_path('routes/blog.php'));

    $expected === null
        ? expect($routes)->not->toContain('->middleware(')
        : expect($routes)->toContain($expected);
})->with([
    'own role' => ['auth', 'own-role', "['auth', 'portal:blog']"],
    'staff' => ['auth', 'staff', "['auth', 'portal:staff']"],
    'normal user' => ['auth', 'user', "['auth', 'portal:user']"],
    'open' => ['open', null, null],
]);

it('opts a subdomain into public self-registration', function () {
    answerSubdomainPrompts($this->artisan('make:subdomain', ['name' => 'blog']), registerAs: "Blog & Vlog Writer's Club")
        ->assertSuccessful();

    expect(resource_path('views/pages/blog/⚡dashboard/dashboard.blade.php'))->toBeFile();
});

it('scaffolds its own error page only when asked', function () {
    answerSubdomainPrompts($this->artisan('make:subdomain', ['name' => 'blog']), style: 'own')->assertSuccessful();

    expect(resource_path('views/errors/blog/page.blade.php'))->toBeFile();
});

it('rejects invalid names and existing portals', function () {
    $this->artisan('make:subdomain', ['name' => 'Blog Name!'])->assertFailed();
    $this->artisan('make:subdomain', ['name' => 'app'])->assertFailed();

    expect(resource_path('css/blog.css'))->not->toBeFile();
});

it('refuses to run in single-domain mode', function () {
    config(['multidomain.single_domain' => true]);

    $this->artisan('make:subdomain', ['name' => 'blog'])->assertFailed();

    expect(resource_path('css/blog.css'))->not->toBeFile()
        ->and(Role::where('slug', 'blog')->exists())->toBeFalse();
});

it('writes into the sandbox, never the real checkout', function () {
    answerSubdomainPrompts($this->artisan('make:subdomain', ['name' => 'blog']))->assertSuccessful();

    expect(resource_path())->toStartWith($this->sandbox)
        ->and(dirname(__DIR__, 3).'/routes/blog.php')->not->toBeFile();
});
