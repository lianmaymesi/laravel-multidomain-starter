<?php

namespace App\Console\Commands;

use App\Notifications\HealthStatusChanged;
use App\Support\Health\HealthChecker;
use App\Support\Health\Status;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;

/**
 * php artisan health:check — for deploy scripts, cron and humans.
 * Exit code 1 when something failed (or, with --strict, warned).
 */
class HealthCheckCommand extends Command
{
    public const LAST_STATUS_KEY = 'health:last-status';

    protected $signature = 'health:check
        {--json : Print the full report as JSON}
        {--strict : Treat warnings as failures (exit 1)}
        {--notify : Email HEALTH_NOTIFY_MAIL if the overall status changed since the last --notify run}';

    protected $description = 'Run every health check (database, cache, queue, storage, scheduler, disk, portals, modules)';

    public function handle(HealthChecker $health): int
    {
        $report = $health->report(fresh: true);
        $status = $report->status();

        if ($this->option('json')) {
            $this->line(json_encode($report->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        } else {
            foreach ($report->checks as $check) {
                $result = $check['result'];
                $badge = match ($result->status) {
                    Status::Ok => '<fg=green>OK</>',
                    Status::Warning => '<fg=yellow>WARN</>',
                    Status::Failed => '<fg=red>FAIL</>',
                };

                $this->components->twoColumnDetail("{$check['label']} <fg=gray>({$result->durationMs} ms)</>", "{$badge} {$result->message}");
            }

            $this->newLine();
            $this->components->{$status === Status::Failed ? 'error' : ($status === Status::Warning ? 'warn' : 'info')}('Overall: '.strtoupper($status->value));
        }

        if ($this->option('notify')) {
            $this->notifyOnChange($report, $status);
        }

        return $status === Status::Failed || ($this->option('strict') && $status === Status::Warning)
            ? self::FAILURE
            : self::SUCCESS;
    }

    /** Email only when the status changed — not every five minutes while it's down. */
    private function notifyOnChange($report, Status $status): void
    {
        $previous = Status::tryFrom((string) Cache::get(self::LAST_STATUS_KEY));
        Cache::forever(self::LAST_STATUS_KEY, $status->value);

        $recipients = config('health.notify', []);

        if ($recipients === [] || $previous === $status || ($previous === null && $status === Status::Ok)) {
            return;
        }

        Notification::route('mail', $recipients)->notify(new HealthStatusChanged($report, $previous));
    }
}
