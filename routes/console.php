<?php

use App\Jobs\ProcessPendingAccountDeletions;
use App\Jobs\PruneAccountExports;
use App\Jobs\RefreshExchangeRates;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Process accounts past the 30-day grace period — runs daily at 02:00
Schedule::job(new ProcessPendingAccountDeletions)->dailyAt('02:00')->onOneServer();

// Prune expired data export files — runs daily at 03:00
Schedule::job(new PruneAccountExports)->dailyAt('03:00')->onOneServer();

// Refresh currency exchange rates — runs daily at 01:00
Schedule::job(new RefreshExchangeRates)->dailyAt('01:00')->onOneServer();
