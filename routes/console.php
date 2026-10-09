<?php

use Atrium\Core\Jobs\ProcessPendingAccountDeletions;
use Atrium\Core\Jobs\PruneAccountExports;
use Atrium\Core\Support\Health\Checks\SchedulerCheck;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Process accounts past the 30-day grace period — runs daily at 02:00
Schedule::job(new ProcessPendingAccountDeletions)->dailyAt('02:00')->onOneServer();

// Prune expired data export files — runs daily at 03:00
Schedule::job(new PruneAccountExports)->dailyAt('03:00')->onOneServer();

// Health: the scheduler proves it's running (checked by SchedulerCheck), and
// emails HEALTH_NOTIFY_MAIL whenever the overall status changes.
Schedule::call(fn () => Cache::forever(SchedulerCheck::HEARTBEAT_KEY, time()))
    ->everyMinute()
    ->name('health:heartbeat');

Schedule::command('health:check --notify')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->when(fn () => config('health.notify') !== []);

// Backups (config/backup.php) — only when BACKUP_ENABLED=true. Clean before
// running so the new backup never pushes out the newest old one; the monitor
// emails BACKUP_NOTIFY_MAIL when the newest backup is too old or too big.
$backupsEnabled = fn () => (bool) config('backup.enabled');

Schedule::command('backup:clean')
    ->dailyAt(config('backup.schedule.clean_at', '01:00'))
    ->onOneServer()
    ->withoutOverlapping()
    ->when($backupsEnabled);

Schedule::command('backup:run')
    ->dailyAt(config('backup.schedule.run_at', '01:30'))
    ->onOneServer()
    ->withoutOverlapping()
    ->when($backupsEnabled);

Schedule::command('backup:monitor')
    ->dailyAt(config('backup.schedule.monitor_at', '09:00'))
    ->onOneServer()
    ->when($backupsEnabled);
