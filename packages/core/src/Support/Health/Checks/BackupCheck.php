<?php

namespace Atrium\Core\Support\Health\Checks;

use Atrium\Core\Support\Health\Check;
use Atrium\Core\Support\Health\Result;
use Spatie\Backup\Config\Config;
use Spatie\Backup\Tasks\Monitor\BackupDestinationStatus;
use Spatie\Backup\Tasks\Monitor\BackupDestinationStatusFactory;

/**
 * Runs the backup monitor (config/backup.php → monitor_backups) against every
 * destination: reachable, newest backup recent enough, storage under the cap.
 * Backups switched off is only worth a warning in production.
 */
class BackupCheck implements Check
{
    public function name(): string
    {
        return 'backups';
    }

    public function label(): string
    {
        return 'Backups';
    }

    public function run(): Result
    {
        if (! config('backup.enabled')) {
            return app()->isProduction()
                ? Result::warning('Backups are off — set BACKUP_ENABLED=true.')
                : Result::ok('Off (BACKUP_ENABLED=false).');
        }

        $destinations = [];
        $problems = [];
        $empty = [];
        $stalest = null;

        /** @var BackupDestinationStatus $status */
        foreach (BackupDestinationStatusFactory::createForMonitorConfig(app(Config::class)->monitoredBackups) as $status) {
            $destination = $status->backupDestination();
            $disk = $destination->diskName();

            if (! $destination->isReachable()) {
                $destinations[$disk] = ['reachable' => false];
                $problems[] = "{$disk}: unreachable.";

                continue;
            }

            $newest = $destination->newestBackup();

            $destinations[$disk] = [
                'reachable' => true,
                'newest' => $newest?->date()->toAtomString(),
                'count' => $destination->backups()->count(),
                'used_mb' => round($destination->usedStorage() / 1024 / 1024, 1),
            ];

            if ($newest === null) {
                $empty[] = $disk;

                continue;
            }

            if (! $status->isHealthy()) {
                $problems[] = "{$disk}: ".$status->failureMessages()->pluck('message')->implode(' ');
            }

            if ($stalest === null || $newest->date()->lt($stalest)) {
                $stalest = $newest->date();
            }
        }

        $meta = ['destinations' => $destinations];

        if ($problems !== []) {
            return Result::failed(implode(' ', $problems), $meta);
        }

        if ($empty !== []) {
            return Result::warning('No backup yet on '.implode(', ', $empty).' — run `php artisan backup:run`.', $meta);
        }

        return Result::ok('Latest backup '.$stalest?->diffForHumans().'.', $meta);
    }
}
