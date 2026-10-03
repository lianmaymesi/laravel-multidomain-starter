<?php

use App\Support\Health\Checks;

return [

    /*
    |--------------------------------------------------------------------------
    | Health checks
    |--------------------------------------------------------------------------
    |
    | /up      — liveness: the app boots. Laravel's default, no dependencies.
    | /health  — readiness: every check below. Public callers get only the
    |            overall status; send HEALTH_TOKEN (X-Health-Token header or
    |            ?token=) to get each check's detail. 200 when ok/warning,
    |            503 when anything failed.
    |
    | Also: `php artisan health:check` and Backoffice → System health.
    | Modules add checks with Module::contribute('health.checks', [...]).
    |
    */

    'token' => env('HEALTH_TOKEN'),

    // Reuse a report this long, so monitors polling /health add no load.
    'cache_seconds' => (int) env('HEALTH_CACHE_SECONDS', 30),

    'checks' => [
        Checks\EnvironmentCheck::class,
        Checks\DatabaseCheck::class,
        Checks\CacheCheck::class,
        Checks\QueueCheck::class,
        Checks\StorageCheck::class,
        Checks\SchedulerCheck::class,
        Checks\DiskSpaceCheck::class,
        Checks\PortalsCheck::class,
    ],

    'thresholds' => [
        'database_latency_ms' => 500,      // warn above
        'queue_backlog' => 100,            // pending jobs — warn above
        'queue_oldest_minutes' => 15,      // oldest pending job — warn above
        'scheduler_stale_minutes' => 5,    // no scheduler heartbeat for longer — fail
        'disk_free_warning_percent' => 10,
        'disk_free_failed_percent' => 3,
    ],

    // Disks to write/read/delete a probe file on (null = the default disk).
    'disks' => [null, 'public'],

    /*
    | Email when the overall status changes (ok → failed, failed → ok, …).
    | Comma-separated addresses; empty = no emails. Checked every five
    | minutes by the scheduler (`health:check --notify`).
    */
    'notify' => array_values(array_filter(array_map('trim', explode(',', (string) env('HEALTH_NOTIFY_MAIL', ''))))),

];
