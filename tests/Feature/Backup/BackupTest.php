<?php

use Atrium\Core\Support\Health\Checks\BackupCheck;
use Atrium\Core\Support\Health\Status;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Backup\Config\Config;
use Spatie\Backup\Notifications\EventHandler;
use Spatie\Backup\Notifications\Notifiable;
use Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification;
use Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification;

// `backup:run --disable-notifications` switches spatie's handler off statically.
beforeEach(fn () => EventHandler::enable());

/** Re-read config('backup') into spatie's Config after changing it. */
function rebindBackupConfig(array $overrides = []): void
{
    config($overrides);
    app()->forgetInstance(Config::class);
}

function backupName(): string
{
    return config('backup.backup.name');
}

/** Loads config/backup.php with these env values, then restores them. */
function loadBackupConfigWith(array $env): array
{
    $previous = [];

    foreach ($env as $key => $value) {
        $previous[$key] = $_ENV[$key] ?? null;
        $_ENV[$key] = $_SERVER[$key] = $value;
    }

    Env::enablePutenv(); // rebuild the repository so it sees the new values

    try {
        return require config_path('backup.php');
    } finally {
        foreach ($previous as $key => $value) {
            if ($value === null) {
                unset($_ENV[$key], $_SERVER[$key]);
            } else {
                $_ENV[$key] = $_SERVER[$key] = $value;
            }
        }

        Env::enablePutenv();
    }
}

// ── Config ───────────────────────────────────────────────────────────

it('emails only problems, to BACKUP_NOTIFY_MAIL or else HEALTH_NOTIFY_MAIL', function () {
    $config = loadBackupConfigWith(['BACKUP_NOTIFY_MAIL' => '', 'HEALTH_NOTIFY_MAIL' => 'ops@example.test, oncall@example.test']);

    expect($config['notifications']['mail']['to'])->toBe(['ops@example.test', 'oncall@example.test'])
        ->and($config['notifications']['notifications'][BackupHasFailedNotification::class])->toBe(['mail'])
        ->and($config['notifications']['notifications'][BackupWasSuccessfulNotification::class])->toBe([]);

    $config = loadBackupConfigWith(['BACKUP_NOTIFY_MAIL' => 'backups@example.test', 'HEALTH_NOTIFY_MAIL' => 'ops@example.test']);
    expect($config['notifications']['mail']['to'])->toBe(['backups@example.test']);
});

it('sends nothing without recipients', function () {
    $config = loadBackupConfigWith(['BACKUP_NOTIFY_MAIL' => '', 'HEALTH_NOTIFY_MAIL' => '']);

    expect($config['notifications']['mail']['to'])->toBe([])
        ->and(array_filter($config['notifications']['notifications']))->toBe([]);
});

it('backs up to the comma-separated BACKUP_DISKS, defaulting to the local backups disk', function () {
    expect(loadBackupConfigWith(['BACKUP_DISKS' => ''])['backup']['destination']['disks'])->toBe(['backups']);

    $config = loadBackupConfigWith(['BACKUP_DISKS' => 'backups, s3']);

    expect($config['backup']['destination']['disks'])->toBe(['backups', 's3'])
        ->and($config['backup']['destination']['continue_on_failure'])->toBeTrue()
        ->and($config['monitor_backups'][0]['disks'])->toBe(['backups', 's3']);
});

it('keeps earlier backups and temporary files out of the archive', function () {
    expect(config('backup.backup.source.files.exclude'))
        ->toContain(storage_path('app/backups'), storage_path('app/private/livewire-tmp'));
});

// ── Schedule ─────────────────────────────────────────────────────────

it('schedules clean, run and monitor daily, only when backups are enabled', function () {
    $events = collect(app(Schedule::class)->events())
        ->filter(fn ($event) => str_contains($event->command ?? '', 'backup:'))
        ->mapWithKeys(fn ($event) => [trim(str($event->command)->after('artisan')->replace(["'", '"'], '')) => $event]);

    expect($events->keys()->sort()->values()->all())->toBe(['backup:clean', 'backup:monitor', 'backup:run'])
        ->and($events['backup:run']->expression)->toBe('30 1 * * *')
        ->and($events['backup:clean']->expression)->toBe('0 1 * * *');

    config(['backup.enabled' => false]);
    expect($events->every(fn ($event) => ! $event->filtersPass(app())))->toBeTrue();

    config(['backup.enabled' => true]);
    expect($events->every(fn ($event) => $event->filtersPass(app())))->toBeTrue();
});

// ── Health check ─────────────────────────────────────────────────────

it('reports backups switched off, warning only in production', function () {
    config(['backup.enabled' => false]);
    expect(app(BackupCheck::class)->run()->status)->toBe(Status::Ok);

    app()->detectEnvironment(fn () => 'production');
    expect(app(BackupCheck::class)->run()->status)->toBe(Status::Warning);
});

it('warns when no backup has been taken yet', function () {
    Storage::fake('backups');
    rebindBackupConfig(['backup.enabled' => true]);

    $result = app(BackupCheck::class)->run();

    expect($result->status)->toBe(Status::Warning)
        ->and($result->meta['destinations']['backups']['count'])->toBe(0);
});

it('passes with a recent backup and fails with a stale one', function () {
    Storage::fake('backups');
    rebindBackupConfig(['backup.enabled' => true]);

    Storage::disk('backups')->put(backupName().'/'.now()->subHours(3)->format('Y-m-d-H-i-s').'.zip', 'zip');

    $result = app(BackupCheck::class)->run();
    expect($result->status)->toBe(Status::Ok)
        ->and($result->meta['destinations']['backups']['count'])->toBe(1);

    Storage::disk('backups')->deleteDirectory(backupName());
    Storage::disk('backups')->put(backupName().'/'.now()->subDays(3)->format('Y-m-d-H-i-s').'.zip', 'zip');

    expect(app(BackupCheck::class)->run()->status)->toBe(Status::Failed);
});

it('fails when a destination disk is unreachable', function () {
    rebindBackupConfig([
        'backup.enabled' => true,
        'backup.monitor_backups.0.disks' => ['missing-disk'],
    ]);

    $result = app(BackupCheck::class)->run();

    expect($result->status)->toBe(Status::Failed)
        ->and($result->meta['destinations']['missing-disk']['reachable'])->toBeFalse();
});

// ── backup:run ───────────────────────────────────────────────────────

it('writes a zip of the files to the destination disk', function () {
    $source = storage_path('framework/testing/backup-source');
    File::ensureDirectoryExists("{$source}/private/media");
    File::put("{$source}/private/media/photo.jpg", 'image');

    Storage::fake('backups');
    rebindBackupConfig([
        'backup.enabled' => true,
        'backup.backup.source.files.include' => [$source],
        'backup.backup.source.files.relative_path' => $source,
        'backup.backup.temporary_directory' => storage_path('framework/testing/backup-temp'),
        'backup.backup.tries' => 1,
    ]);

    try {
        $this->artisan('backup:run', ['--only-files' => true, '--disable-notifications' => true])->assertSuccessful();
    } finally {
        File::deleteDirectory($source);
        File::deleteDirectory(storage_path('framework/testing/backup-temp'));
    }

    $zips = Storage::disk('backups')->files(backupName());
    expect($zips)->toHaveCount(1);

    $zip = new ZipArchive;
    $zip->open(Storage::disk('backups')->path($zips[0]));
    // spatie joins entry names with DIRECTORY_SEPARATOR; normalise for Windows.
    $entries = collect(range(0, $zip->numFiles - 1))->map(fn ($i) => str_replace('\\', '/', $zip->getNameIndex($i)));
    $zip->close();

    expect($entries)->toContain('private/media/photo.jpg')
        ->and(app(BackupCheck::class)->run()->status)->toBe(Status::Ok);
});

it('emails the recipients when a backup fails', function () {
    Notification::fake();
    rebindBackupConfig([
        'backup.backup.source.files.include' => [base_path('composer.json')],
        'backup.backup.source.files.relative_path' => base_path(),
        'backup.backup.destination.disks' => ['missing-disk'],
        'backup.backup.temporary_directory' => storage_path('framework/testing/backup-temp'),
        'backup.backup.tries' => 1,
        'backup.notifications.mail.to' => ['ops@example.test'],
        'backup.notifications.notifications.'.BackupHasFailedNotification::class => ['mail'],
    ]);

    try {
        $this->artisan('backup:run', ['--only-files' => true])->assertFailed();
    } finally {
        File::deleteDirectory(storage_path('framework/testing/backup-temp'));
    }

    Notification::assertSentTo(new Notifiable, BackupHasFailedNotification::class);
});
