<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

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

        $stubs = base_path('stubs/subdomain');
        $created = [];

        $created[] = $this->putFromStub("{$stubs}/css.stub", resource_path("css/{$name}.css"), $name);
        $created[] = $this->putFromStub("{$stubs}/js.stub", resource_path("js/{$name}.js"), $name);
        $created[] = $this->putFromStub("{$stubs}/layout.stub", resource_path("views/layouts/{$name}.blade.php"), $name);
        $created[] = $this->putFromStub("{$stubs}/route.stub", base_path("routes/{$name}.php"), $name);

        $pageDir = resource_path("views/pages/{$name}/⚡dashboard");
        File::ensureDirectoryExists($pageDir);
        $created[] = $this->putFromStub("{$stubs}/dashboard.stub", "{$pageDir}/dashboard.php", $name);
        $created[] = $this->putFromStub("{$stubs}/dashboard.blade.stub", "{$pageDir}/dashboard.blade.php", $name);

        foreach ($created as $path) {
            $this->line('<info>✔</info> Created '.str_replace(base_path().DIRECTORY_SEPARATOR, '', $path));
        }

        $this->newLine();
        $this->line('Next steps (manual):');
        $this->line("1. Add 'name' => 'name.'.env('APP_MAIN_DOMAIN') to config/multidomain.php sub_domains array (replace name with {$name})");
        $this->line("2. Add a Route::domain(config('multidomain.sub_domains.{$name}'))->group(fn () => include __DIR__.'/{$name}.php'); block to routes/web.php");
        $this->line("3. Add \"resources/css/{$name}.css\" and \"resources/js/{$name}.js\" to vite.config.js input array");
        $this->line("4. Add {$name}.<APP_MAIN_DOMAIN> to your local hosts/Herd config");

        return self::SUCCESS;
    }

    private function putFromStub(string $stubPath, string $destination, string $name): string
    {
        $contents = str_replace('{{ name }}', $name, File::get($stubPath));

        File::ensureDirectoryExists(dirname($destination));
        File::put($destination, $contents);

        return $destination;
    }
}
