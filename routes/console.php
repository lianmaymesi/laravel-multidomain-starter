<?php

use App\Jobs\ProcessPendingAccountDeletions;
use App\Jobs\PruneAccountExports;
use App\Support\Health\Checks\SchedulerCheck;
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
