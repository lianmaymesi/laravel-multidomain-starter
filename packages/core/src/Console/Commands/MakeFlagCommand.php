<?php

namespace Atrium\Core\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeFlagCommand extends Command
{
    protected $signature = 'make:flag
        {name : The flag name, e.g. "NewCurrencyPicker" or "new-currency-picker"}
        {--user : Decide the flag per user instead of per portal}';

    protected $description = 'Create a feature flag class under app/Features (per portal, or per user with --user)';

    public function handle(): int
    {
        $input = (string) $this->argument('name');

        if (! preg_match('/^[A-Za-z][A-Za-z0-9 _-]*$/', $input)) {
            $this->components->error('Invalid flag name — use letters, numbers, spaces, dashes or underscores, starting with a letter.');

            return self::FAILURE;
        }

        $studly = Str::studly($input);
        $path = app_path("Features/{$studly}.php");

        if (File::exists($path)) {
            $this->components->error("Flag [{$studly}] already exists at app/Features/{$studly}.php.");

            return self::FAILURE;
        }

        $stub = base_path('stubs/flag/'.($this->option('user') ? 'user' : 'portal').'.stub');

        File::ensureDirectoryExists(dirname($path));
        File::put($path, strtr(File::get($stub), [
            '{{ Name }}' => $studly,
            '{{ name }}' => Str::kebab($studly),
            '{{ Title }}' => Str::headline($studly),
        ]));

        $this->components->info("Flag [app/Features/{$studly}.php] created.");
        $this->line('  Check it with Flags::active('.$studly.'::class) or @flag('.$studly.'::class).');
        $this->line('  Flag classes are discovered automatically — it is already listed on Backoffice → Feature Flags.');

        return self::SUCCESS;
    }
}
