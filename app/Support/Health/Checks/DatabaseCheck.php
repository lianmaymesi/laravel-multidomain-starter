<?php

namespace App\Support\Health\Checks;

use App\Support\Health\Check;
use App\Support\Health\Result;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;

/** Connects, measures a round-trip, and spots migrations a deploy forgot to run. */
class DatabaseCheck implements Check
{
    public function __construct(private Migrator $migrator) {}

    public function name(): string
    {
        return 'database';
    }

    public function label(): string
    {
        return 'Database';
    }

    public function run(): Result
    {
        $started = hrtime(true);
        DB::select('select 1');
        $latency = round((hrtime(true) - $started) / 1e6, 1);

        $meta = ['connection' => DB::getDefaultConnection(), 'latency_ms' => $latency];

        if (! $this->migrator->repositoryExists()) {
            return Result::failed('Connected, but migrations have never run.', $meta);
        }

        $files = $this->migrator->getMigrationFiles([database_path('migrations'), ...$this->migrator->paths()]);
        $pending = array_values(array_diff(array_keys($files), $this->migrator->getRepository()->getRan()));

        if ($pending !== []) {
            return Result::warning(count($pending).' pending migration(s) — run `php artisan migrate`.', [...$meta, 'pending' => count($pending)]);
        }

        if ($latency > (int) config('health.thresholds.database_latency_ms', 500)) {
            return Result::warning("Slow: {$latency} ms round-trip.", $meta);
        }

        return Result::ok("Connected ({$latency} ms).", $meta);
    }
}
