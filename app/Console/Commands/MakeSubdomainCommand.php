<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

use function Laravel\Prompts\select;

class MakeSubdomainCommand extends Command
{
    protected $signature = 'make:subdomain {name : The subdomain key, e.g. "blog"}';

    protected $description = 'Scaffold a new subdomain portal (css, js, layout, route file, dashboard page)';

    public function handle(): int
    {
        $name = $this->argument('name');

        if (! preg_match('/^[a-z][a-z0-9-]*$/', $name)) {
            $this->error('Subdomain name must be lowercase alphanumeric with hyphens, starting with a letter.');

            return self::FAILURE;
        }

        if (array_key_exists($name, config('multidomain.sub_domains', []))) {
            $this->error("A subdomain named \"{$name}\" already exists in config/multidomain.php.");

            return self::FAILURE;
        }

        if (config('multidomain.single_domain')) {
            $this->error('Cannot scaffold a new subdomain while APP_SINGLE_DOMAIN is true.');
            $this->line('Every portal shares one host in single-domain mode, so a freshly generated "/" route would collide with the existing ones.');
            $this->line('Set APP_SINGLE_DOMAIN=false in .env, then run this command again.');

            return self::FAILURE;
        }

        $access = select(
            label: 'Who should be able to access this portal?',
            options: [
                'open' => 'Open — anyone can access, no login required',
                'auth' => 'Requires authentication',
            ],
            default: 'auth',
        );

        $role = $access === 'auth'
            ? select(
                label: 'Access level',
                options: [
                    'user' => 'Normal user',
                    'staff' => 'Staff (role-based access, for future use)',
                ],
                default: 'user',
            )
            : null;

        $middleware = match (true) {
            $role === 'staff' => "['auth', 'portal:staff']",
            $role === 'user' => "['auth', 'portal:user']",
            default => null,
        };

        $stubs = base_path('stubs/subdomain');
        $replacements = [
            '{{ name }}' => $name,
            '{{ middleware }}' => $middleware ? "->middleware({$middleware})" : '',
        ];
        $created = [];

        $created[] = $this->putFromStub("{$stubs}/css.stub", resource_path("css/{$name}.css"), $replacements);
        $created[] = $this->putFromStub("{$stubs}/js.stub", resource_path("js/{$name}.js"), $replacements);
        $created[] = $this->putFromStub("{$stubs}/layout.stub", resource_path("views/layouts/{$name}.blade.php"), $replacements);
        $created[] = $this->putFromStub("{$stubs}/route.stub", base_path("routes/{$name}.php"), $replacements);

        $pageDir = resource_path("views/pages/{$name}/⚡dashboard");
        File::ensureDirectoryExists($pageDir);
        $created[] = $this->putFromStub("{$stubs}/dashboard.stub", "{$pageDir}/dashboard.php", $replacements);
        $created[] = $this->putFromStub("{$stubs}/dashboard.blade.stub", "{$pageDir}/dashboard.blade.php", $replacements);

        foreach ($created as $path) {
            $this->line('<info>✔</info> Created '.str_replace(base_path().DIRECTORY_SEPARATOR, '', $path));
        }

        $this->newLine();
        $this->line('Next steps (manual):');
        $this->line("1. Add 'name' => 'name.'.env('APP_MAIN_DOMAIN') to config/multidomain.php sub_domains array (replace name with {$name})");
        $this->line('2. Add this block to routes/web.php:');
        $this->line("   Route::domain(config('multidomain.sub_domains.{$name}'))->name('{$name}.')->group(fn () => include __DIR__.'/{$name}.php');");
        $this->line("3. Add \"resources/css/{$name}.css\" and \"resources/js/{$name}.js\" to vite.config.js input array");
        $this->line("4. Add {$name}.<APP_MAIN_DOMAIN> to your local hosts/Herd config");

        return self::SUCCESS;
    }

    /** @param array<string, string> $replacements */
    private function putFromStub(string $stubPath, string $destination, array $replacements): string
    {
        $contents = strtr(File::get($stubPath), $replacements);

        File::ensureDirectoryExists(dirname($destination));
        File::put($destination, $contents);

        return $destination;
    }
}
