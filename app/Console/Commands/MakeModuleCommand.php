<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModuleCommand extends Command
{
    protected $signature = 'make:module {name : The module name, e.g. "Invoices" or "invoice-reports"}';

    protected $description = 'Scaffold a new feature module under app/Modules (provider, routes, page, migrations dir, test) and register its on/off toggle';

    public function handle(): int
    {
        $input = (string) $this->argument('name');

        if (! preg_match('/^[A-Za-z][A-Za-z0-9 _-]*$/', $input)) {
            $this->components->error('Invalid module name — use letters, numbers, spaces, dashes or underscores, starting with a letter.');

            return self::FAILURE;
        }

        $studly = Str::studly($input);
        $slug = Str::kebab($studly);
        $moduleDir = app_path("Modules/{$studly}");

        if (File::exists($moduleDir)) {
            $this->components->error("Module [{$studly}] already exists at app/Modules/{$studly}.");

            return self::FAILURE;
        }

        if (array_key_exists($slug, config('modules', []))) {
            $this->components->error("A module toggle named \"{$slug}\" is already registered in config/modules.php.");

            return self::FAILURE;
        }

        $replacements = [
            '{{ Name }}' => $studly,
            '{{ name }}' => $slug,
            '{{ NAME }}' => strtoupper(str_replace('-', '_', $slug)),
            '{{ Title }}' => Str::headline($studly),
        ];

        $stubs = base_path('stubs/module');
        $pageDir = "{$moduleDir}/resources/views/livewire/⚡index";
        $testFile = base_path("tests/Feature/Modules/{$studly}ModuleTest.php");

        $created = [
            $this->putFromStub("{$stubs}/provider.stub", "{$moduleDir}/{$studly}ServiceProvider.php", $replacements),
            $this->putFromStub("{$stubs}/routes.stub", "{$moduleDir}/routes/backoffice.php", $replacements),
            $this->putFromStub("{$stubs}/page.stub", "{$pageDir}/index.php", $replacements),
            $this->putFromStub("{$stubs}/page.blade.stub", "{$pageDir}/index.blade.php", $replacements),
            $this->putFromStub("{$stubs}/test.stub", $testFile, $replacements),
        ];

        File::ensureDirectoryExists("{$moduleDir}/database/migrations");
        File::put("{$moduleDir}/database/migrations/.gitkeep", '');
        $created[] = "{$moduleDir}/database/migrations/.gitkeep";

        $this->registerToggle($slug, $replacements['{{ NAME }}']);

        $this->components->info("Module [{$studly}] created.");
        $this->components->bulletList([
            ...array_map(fn (string $path) => Str::after(str_replace('\\', '/', $path), str_replace('\\', '/', base_path()).'/'), $created),
            'config/modules.php (added "'.$slug.'" toggle)',
            '.env.example (added MODULE_'.$replacements['{{ NAME }}'].')',
        ]);

        $this->newLine();
        $this->line('  Next steps:');
        $this->line("   1. Add models, services and Livewire pages under app/Modules/{$studly}/");
        $this->line("   2. Add tables with:  php artisan make:migration create_x_table --path=app/Modules/{$studly}/database/migrations");
        $this->line("   3. Seed the permission:  php artisan db:seed --class=RolePermissionSeeder  (then visit /backoffice/{$slug})");
        $this->line("   4. Switch it off any time with MODULE_{$replacements['{{ NAME }}']}=false in .env");
        $this->line('   Providers are auto-discovered — nothing else to register. See app/Modules/README.md.');

        return self::SUCCESS;
    }

    /**
     * Adds `'{slug}' => (bool) env('MODULE_{NAME}', true),` to
     * config/modules.php and a commented line to .env.example.
     */
    private function registerToggle(string $slug, string $envName): void
    {
        $config = config_path('modules.php');
        $contents = File::get($config);

        $entry = "    '{$slug}' => (bool) env('MODULE_{$envName}', true),\n\n";
        $position = strrpos($contents, '];');

        File::put($config, substr($contents, 0, $position).$entry.substr($contents, $position));

        $envExample = base_path('.env.example');

        if (File::exists($envExample)) {
            $env = File::get($envExample);

            File::put($envExample, str_contains($env, '# MODULE_')
                ? preg_replace('/^(# MODULE_[A-Z0-9_]+=true)$(?![\s\S]*^# MODULE_)/m', "$1\n# MODULE_{$envName}=true", $env, 1)
                : rtrim($env)."\n\n# MODULE_{$envName}=true\n");
        }
    }

    /**
     * @param  array<string, string>  $replacements
     */
    private function putFromStub(string $stubPath, string $destination, array $replacements): string
    {
        File::ensureDirectoryExists(dirname($destination));
        File::put($destination, strtr(File::get($stubPath), $replacements));

        return $destination;
    }
}
