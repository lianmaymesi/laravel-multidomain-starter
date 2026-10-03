<?php

namespace App\Support\Health\Checks;

use App\Support\Health\Check;
use App\Support\Health\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;

/**
 * The queue connection, its backlog, and jobs that failed. A growing backlog
 * or an old pending job usually means no worker is running.
 */
class QueueCheck implements Check
{
    public function name(): string
    {
        return 'queue';
    }

    public function label(): string
    {
        return 'Queue';
    }

    public function run(): Result
    {
        $connection = config('queue.default');
        $driver = config("queue.connections.{$connection}.driver");
        $meta = ['connection' => $connection, 'driver' => $driver];

        if ($driver === 'sync') {
            return app()->isProduction()
                ? Result::warning('Jobs run inline (sync driver) — set QUEUE_CONNECTION and run a worker.', $meta)
                : Result::ok('Jobs run inline (sync driver).', $meta);
        }

        $warnings = [];

        if ($driver === 'database') {
            $table = config("queue.connections.{$connection}.table", 'jobs');

            if (! Schema::hasTable($table)) {
                return Result::failed("Queue table \"{$table}\" is missing — run `php artisan migrate`.", $meta);
            }

            $meta['pending'] = DB::table($table)->count();
            $oldest = DB::table($table)->min('available_at');
            $meta['oldest_minutes'] = $oldest ? (int) floor((time() - (int) $oldest) / 60) : 0;

            if ($meta['oldest_minutes'] > (int) config('health.thresholds.queue_oldest_minutes', 15)) {
                $warnings[] = "oldest pending job is {$meta['oldest_minutes']} min old — is a worker running?";
            }
        } else {
            $meta['pending'] = Queue::connection($connection)->size();
        }

        if ($meta['pending'] > (int) config('health.thresholds.queue_backlog', 100)) {
            $warnings[] = "{$meta['pending']} jobs waiting";
        }

        $failedTable = config('queue.failed.table', 'failed_jobs');

        if (Schema::hasTable($failedTable) && ($meta['failed'] = DB::table($failedTable)->count()) > 0) {
            $warnings[] = "{$meta['failed']} failed job(s) — see `php artisan queue:failed`";
        }

        return $warnings === []
            ? Result::ok("Connection \"{$connection}\": {$meta['pending']} pending.", $meta)
            : Result::warning(ucfirst(implode('; ', $warnings)).'.', $meta);
    }
}
