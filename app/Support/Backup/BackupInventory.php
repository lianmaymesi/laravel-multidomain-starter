<?php

namespace App\Support\Backup;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Spatie\Backup\BackupDestination\Backup;
use Spatie\Backup\BackupDestination\BackupDestination;
use Spatie\Backup\Config\Config;
use Spatie\Backup\Tasks\Monitor\BackupDestinationStatus;
use Spatie\Backup\Tasks\Monitor\BackupDestinationStatusFactory;

/**
 * What's on each backup destination (config/backup.php → monitor_backups),
 * for Backoffice → Backups. find() is the only way to turn a disk + path from
 * a request into a backup, so nothing outside the backup folders can be
 * downloaded or deleted.
 */
class BackupInventory
{
    /**
     * @return array<int, array{disk: string, reachable: bool, healthy: bool, problems: array<int, string>, used_bytes: float, backups: array<int, array{path: string, date: Carbon, size_bytes: float}>}>
     */
    public function destinations(): array
    {
        return $this->statuses()->map(function (BackupDestinationStatus $status) {
            $destination = $status->backupDestination();
            $reachable = $destination->isReachable();
            $healthy = $status->isHealthy();

            return [
                'disk' => $destination->diskName(),
                'reachable' => $reachable,
                'healthy' => $healthy,
                'problems' => $status->failureMessages()->map(fn (array $failure) => $failure['message'])->values()->all(),
                'used_bytes' => $reachable ? $destination->usedStorage() : 0.0,
                'backups' => $reachable
                    // toBase(): mapping a BackupCollection would type the rows as Backups.
                    ? $destination->backups()->toBase()->map(fn (Backup $backup) => [
                        'path' => $backup->path(),
                        'date' => $backup->date(),
                        'size_bytes' => $backup->sizeInBytes(),
                    ])->values()->all()
                    : [],
            ];
        })->values()->all();
    }

    /** A backup on one of the configured destinations, or null. */
    public function find(string $disk, string $path): ?Backup
    {
        $destination = $this->destination($disk);

        if ($destination === null || ! $destination->isReachable()) {
            return null;
        }

        return $destination->backups()->first(fn (Backup $backup) => $backup->path() === $path);
    }

    public function destination(string $disk): ?BackupDestination
    {
        return $this->statuses()
            ->map(fn (BackupDestinationStatus $status) => $status->backupDestination())
            ->first(fn (BackupDestination $destination) => $destination->diskName() === $disk);
    }

    /** @return Collection<int, BackupDestinationStatus> */
    private function statuses()
    {
        return BackupDestinationStatusFactory::createForMonitorConfig(app(Config::class)->monitoredBackups);
    }
}
