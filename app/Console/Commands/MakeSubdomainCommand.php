<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;

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

        $accessLevel = $access === 'auth'
            ? select(
                label: 'Access level',
                options: [
                    'own-role' => "This portal's own \"{$name}\" role (recommended — only users granted that role get in)",
                    'user' => 'Normal user (global, same gate as the "app" portal)',
                    'staff' => 'Staff (global, same gate as the "backoffice" portal)',
                ],
                default: 'own-role',
            )
            : null;

        $middleware = match ($accessLevel) {
            'staff' => "['auth', 'portal:staff']",
            'user' => "['auth', 'portal:user']",
            'own-role' => "['auth', 'portal:{$name}']",
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

        $role = Role::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        $this->line("<info>✔</info> Role \"{$role->name}\" ready — protected from deletion in the backoffice roles screen.");

        $this->newLine();
        $this->line('Next steps (manual):');
        $this->line("1. Add 'name' => 'name.'.env('APP_MAIN_DOMAIN') to config/multidomain.php sub_domains array (replace name with {$name})");
        $this->line('2. Add this block to routes/web.php:');
        $this->line("   Route::domain(config('multidomain.sub_domains.{$name}'))->name('{$name}.')->group(fn () => include __DIR__.'/{$name}.php');");
        $this->line("3. Add {$name}.<APP_MAIN_DOMAIN> to your local hosts/Herd config");
        $this->line("4. Assign the \"{$name}\" role to any user who should access this portal and be redirected here after login.");
        $this->line('   (vite.config.js picks up the new css/js entries automatically — no edit needed.)');

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
