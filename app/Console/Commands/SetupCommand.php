<?php

namespace App\Console\Commands;

use App\Support\Modules\Module;
use Database\Seeders\AdminUserSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

/**
 * First-run setup for a fresh clone: app key, migrations, base seed
 * (roles, Super Admin, module defaults), optional demo accounts, then a
 * summary of where everything is and how to sign in. Safe to re-run.
 */
class SetupCommand extends Command
{
    protected $signature = 'app:setup
        {--demo : Also create demo accounts for every portal (never in production)}
        {--fresh : Drop all tables and start over (asks first)}
        {--force : Run in production / skip the --fresh confirmation}';

    protected $description = 'Set up the app in one go: key, migrations, Super Admin, defaults and (optionally) demo accounts';

    public function handle(): int
    {
        if ($this->option('demo') && $this->laravel->isProduction()) {
            $this->components->error('Demo accounts are never created in production.');

            return self::FAILURE;
        }

        if ($this->laravel->isProduction() && ! $this->option('force')
            && ! $this->confirm('This is a production environment. Run setup anyway?')) {
            return self::FAILURE;
        }

        if (blank(config('app.key'))) {
            $this->components->task('Generating app key', fn () => $this->callSilently('key:generate', ['--force' => true]) === 0);
        }

        if ($this->option('fresh')) {
            if (! $this->option('force') && ! $this->confirm('Drop ALL tables and data and start over?')) {
                return self::FAILURE;
            }

            $this->components->task('Dropping tables and migrating', fn () => $this->callSilently('migrate:fresh', ['--force' => true]) === 0);
        } else {
            $this->components->task('Migrating', fn () => $this->callSilently('migrate', ['--force' => true]) === 0);
        }

        $this->components->task('Seeding roles, Super Admin and module defaults', fn () => $this->callSilently('db:seed', ['--class' => DatabaseSeeder::class, '--force' => true]) === 0);

        if ($this->option('demo')) {
            $this->components->task('Creating demo accounts', fn () => $this->callSilently('db:seed', ['--class' => DemoSeeder::class, '--force' => true]) === 0);
        }

        $this->summary();

        return self::SUCCESS;
    }

    private function summary(): void
    {
        $this->newLine();
        $this->components->info('Ready.');

        $this->components->twoColumnDetail('<fg=gray>Portal</>', '<fg=gray>URL</>');
        foreach ([
            'Landing' => 'index',
            'Sign in' => 'auth.login',
            'App' => 'app.dashboard',
            'Account' => 'account.index',
            'Backoffice' => 'backoffice.dashboard',
        ] as $label => $route) {
            if (Route::has($route)) {
                $this->components->twoColumnDetail($label, route($route));
            }
        }

        $this->newLine();
        $this->components->twoColumnDetail('<fg=gray>Sign in as</>', '<fg=gray>Password</>');
        $this->components->twoColumnDetail(
            'Super Admin  '.config('multidomain.setup.admin_email'),
            $this->passwordSource(AdminUserSeeder::$generatedPassword, 'ADMIN_PASSWORD', 'multidomain.setup.admin_password'),
        );

        if ($this->option('demo')) {
            foreach (DemoSeeder::ACCOUNTS as $email => [$name, , , $portal]) {
                $this->components->twoColumnDetail(
                    "{$name}  {$email}  <fg=gray>({$portal})</>",
                    $this->passwordSource(DemoSeeder::$generatedPassword, 'DEMO_PASSWORD', 'multidomain.setup.demo_password'),
                );
            }
        }

        // Generated passwords on their own lines, so they're never wrapped or truncated.
        if (AdminUserSeeder::$generatedPassword !== null) {
            $this->newLine();
            $this->line('  <fg=yellow>Super Admin password (generated — shown once):</> <options=bold>'.AdminUserSeeder::$generatedPassword.'</>');
        }

        if ($this->option('demo') && DemoSeeder::$generatedPassword !== null) {
            $this->line('  <fg=yellow>Demo accounts password (generated — shown once):</> <options=bold>'.DemoSeeder::$generatedPassword.'</>');
        }

        $this->newLine();
        $this->components->twoColumnDetail('Modules on', implode(', ', Module::enabledNames()) ?: 'none');

        $this->newLine();
        $this->line('  Next: <options=bold>composer dev</> (server, queue, Vite). Change the generated passwords after signing in.');

        if (in_array(config('multidomain.main_domain'), [null, '', 'localhost'], true)) {
            $this->line('  Tip: set <options=bold>APP_MAIN_DOMAIN</> (e.g. yourapp.test with Laravel Herd) so each portal gets its own subdomain.');
        }
    }

    private function passwordSource(?string $generated, string $envKey, string $configKey): string
    {
        return match (true) {
            $generated !== null => '<fg=yellow>generated, see below</>',
            filled(config($configKey)) => "{$envKey} from .env",
            default => 'unchanged',
        };
    }
}
