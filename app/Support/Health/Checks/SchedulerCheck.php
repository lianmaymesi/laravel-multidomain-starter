<?php

namespace App\Support\Health\Checks;

use App\Support\Health\Check;
use App\Support\Health\Result;
use Illuminate\Support\Facades\Cache;

/**
 * The scheduler records a heartbeat every minute (routes/console.php). No
 * heartbeat means the `schedule:run` cron isn't set up — so daily account
 * deletions, export pruning and module jobs silently never happen.
 */
class SchedulerCheck implements Check
{
    public const HEARTBEAT_KEY = 'health:scheduler:heartbeat';

    public function name(): string
    {
        return 'scheduler';
    }

    public function label(): string
    {
        return 'Scheduler';
    }

    public function run(): Result
    {
        $last = Cache::get(self::HEARTBEAT_KEY);

        if ($last === null) {
            return Result::warning('No heartbeat yet — is `php artisan schedule:run` in cron (every minute)?');
        }

        $minutes = (int) floor((time() - (int) $last) / 60);
        $meta = ['last_run' => date(DATE_ATOM, (int) $last), 'minutes_ago' => $minutes];

        return $minutes > (int) config('health.thresholds.scheduler_stale_minutes', 5)
            ? Result::failed("Last ran {$minutes} min ago — the scheduler cron has stopped.", $meta)
            : Result::ok($minutes === 0 ? 'Ran this minute.' : "Ran {$minutes} min ago.", $meta);
    }
}
