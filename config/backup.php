<?php

use Spatie\Backup\Notifications\Notifiable;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification;
use Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification;
use Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification;
use Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumAgeInDays;
use Spatie\Backup\Tasks\Monitor\HealthChecks\MaximumStorageInMegabytes;

/*
|--------------------------------------------------------------------------
| Backups (spatie/laravel-backup)
|--------------------------------------------------------------------------
|
| Disaster recovery: one zip per run holding a dump of the whole database
| (every portal shares it) plus storage/app (uploads, media, exports). Code
| lives in git and isn't backed up. Not a module — infrastructure, switched
| on with BACKUP_ENABLED.
|
| php artisan backup:run      take a backup now
| php artisan backup:list     backups per destination, newest first
| php artisan backup:monitor  check age / size, notify when unhealthy
| php artisan backup:clean    apply the retention policy below
|
| The restore procedure is in the README ("Backups").
|
*/

// `?:` rather than env()'s default, so an empty `BACKUP_X=` line in .env
// still falls back.
$name = env('BACKUP_NAME') ?: env('APP_NAME', 'laravel-backup');

$disks = array_values(array_filter(array_map('trim', explode(',', (string) (env('BACKUP_DISKS') ?: 'backups')))));

// Falls back to the health alert recipients, so one address covers both.
$recipients = array_values(array_filter(array_map('trim', explode(',', (string) (env('BACKUP_NOTIFY_MAIL') ?: env('HEALTH_NOTIFY_MAIL', ''))))));

$alert = $recipients === [] ? [] : ['mail'];

$maxStorageMb = (int) (env('BACKUP_MAX_STORAGE_MB') ?: 5000);

return [

    // Off by default: the scheduler only backs up when this is true.
    'enabled' => (bool) env('BACKUP_ENABLED', false),

    // Times for the daily scheduled tasks (server timezone, HH:MM).
    'schedule' => [
        'clean_at' => env('BACKUP_CLEAN_AT') ?: '01:00',
        'run_at' => env('BACKUP_RUN_AT') ?: '01:30',
        'monitor_at' => env('BACKUP_MONITOR_AT') ?: '09:00',
    ],

    'backup' => [

        // Folder name on each destination disk; also how backups are monitored.
        'name' => $name,

        'source' => [
            'files' => [
                'include' => [
                    storage_path('app'),
                ],

                // Earlier backups, and short-lived files nobody restores:
                // upload staging, health probes, expiring account exports.
                'exclude' => [
                    storage_path('app/backups'),
                    storage_path('app/backup-temp'),
                    storage_path('app/private/livewire-tmp'),
                    storage_path('app/private/health-check'),
                    storage_path('app/public/health-check'),
                    storage_path('app/private/exports'),
                ],

                'follow_links' => false,

                'ignore_unreadable_directories' => false,

                // Paths inside the zip start at storage/app (private/…, public/…).
                'relative_path' => storage_path('app'),
            ],

            // Connections to dump. Per-connection options (exclude_tables,
            // useSingleTransaction, …) go under a 'dump' key in config/database.php.
            'databases' => [
                env('DB_CONNECTION', 'sqlite'),
            ],
        ],

        // The zip compresses the dump already; a compressor shells out to
        // gzip, which isn't on every server (or Windows).
        'database_dump_compressor' => null,

        'database_dump_file_timestamp_format' => null,

        'database_dump_filename_base' => 'database',

        'database_dump_file_extension' => '',

        'destination' => [
            'compression_method' => ZipArchive::CM_DEFAULT,

            'compression_level' => 9,

            'filename_prefix' => '',

            // Comma-separated disk names from config/filesystems.php. Use a
            // second, off-server disk (e.g. "backups,s3") in production —
            // a backup on the same server dies with the server.
            'disks' => $disks,

            // With several disks, keep writing to the others when one fails.
            'continue_on_failure' => count($disks) > 1,
        ],

        'temporary_directory' => storage_path('app/backup-temp'),

        // Encrypts the zip (AES-256). Store this password outside the server —
        // without it the backups can't be opened.
        'password' => env('BACKUP_ARCHIVE_PASSWORD'),

        'encryption' => 'default',

        // Re-open the zip after writing it: an unreadable backup is worse than none.
        'verify_backup' => true,

        'tries' => 2,

        'retry_delay' => 60,
    ],

    /*
     * Only problems are emailed, to BACKUP_NOTIFY_MAIL (or HEALTH_NOTIFY_MAIL).
     * No recipients = no emails; failures are still logged.
     */
    'notifications' => [
        'notifications' => [
            BackupHasFailedNotification::class => $alert,
            UnhealthyBackupWasFoundNotification::class => $alert,
            CleanupHasFailedNotification::class => $alert,
            BackupWasSuccessfulNotification::class => [],
            HealthyBackupWasFoundNotification::class => [],
            CleanupWasSuccessfulNotification::class => [],
        ],

        'notifiable' => Notifiable::class,

        'mail' => [
            'to' => $recipients,

            'from' => [
                'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
                'name' => env('MAIL_FROM_NAME', 'Example'),
            ],
        ],

        'slack' => [
            'webhook_url' => '',
            'channel' => null,
            'username' => null,
            'icon' => null,
        ],

        'discord' => [
            'webhook_url' => '',
            'username' => '',
            'avatar_url' => '',
        ],

        'webhook' => [
            'url' => '',
        ],
    ],

    'log_channel' => null,

    /*
     * Checked daily by `backup:monitor` and on every /health run (BackupCheck):
     * unhealthy when the newest backup is older than a day or the backups
     * use more than BACKUP_MAX_STORAGE_MB.
     */
    'monitor_backups' => [
        [
            'name' => $name,
            'disks' => $disks,
            'health_checks' => [
                MaximumAgeInDays::class => 1,
                MaximumStorageInMegabytes::class => $maxStorageMb,
            ],
        ],
    ],

    'cleanup' => [
        // Keep everything for a week, then dailies, weeklies, monthlies,
        // yearlies. Never deletes the newest backup.
        'strategy' => DefaultStrategy::class,

        'default_strategy' => [
            'keep_all_backups_for_days' => 7,
            'keep_daily_backups_for_days' => 16,
            'keep_weekly_backups_for_weeks' => 8,
            'keep_monthly_backups_for_months' => 4,
            'keep_yearly_backups_for_years' => 2,

            // Then delete the oldest until under this size (MB).
            'delete_oldest_backups_when_using_more_megabytes_than' => $maxStorageMb,
        ],

        'tries' => 1,

        'retry_delay' => 0,
    ],

];
