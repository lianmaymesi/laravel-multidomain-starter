<?php

use Atrium\Core\Jobs\RunBackup;
use Atrium\Core\Support\Backup\BackupInventory;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Backoffice → Backups (backups.manage — Super Admin only, since a backup is
 * the whole database). Lists every destination's backups, takes one on the
 * queue, downloads (BackupDownloadController) and deletes. Every action lands
 * in the activity log under "backups".
 */
new #[Layout('layouts.backoffice')] class extends Component
{
    public string $scope = 'full';

    public function mount(): void
    {
        abort_unless(Gate::allows('backups.manage'), 403);
    }

    /** @return array<int, array<string, mixed>> */
    public function destinations(): array
    {
        return app(BackupInventory::class)->destinations();
    }

    /** @return array{enabled: bool, disks: array<int, string>, schedule: array<string, string>, recipients: int, encrypted: bool, queue: string} */
    public function settings(): array
    {
        return [
            'enabled' => (bool) config('backup.enabled'),
            'disks' => config('backup.backup.destination.disks', []),
            'schedule' => config('backup.schedule', []),
            'recipients' => count((array) config('backup.notifications.mail.to', [])),
            'encrypted' => filled(config('backup.backup.password')),
            'queue' => (string) config('queue.default'),
        ];
    }

    public function backUpNow(): void
    {
        abort_unless(Gate::allows('backups.manage'), 403);

        $scope = in_array($this->scope, RunBackup::SCOPES, true) ? $this->scope : 'full';

        activity('backups')
            ->causedBy(auth()->user())
            ->withProperties(['scope' => $scope])
            ->log('backup requested');

        RunBackup::dispatch($scope, auth()->id());

        session()->flash('status', config('queue.default') === 'sync'
            ? __('Backup finished — see the list below.')
            : __('Backup started on the queue. It appears below when done; refresh in a minute.'));
    }

    public function delete(string $disk, string $path): void
    {
        abort_unless(Gate::allows('backups.manage'), 403);

        $backup = app(BackupInventory::class)->find($disk, $path);

        abort_if($backup === null, 404);

        $backup->delete();

        activity('backups')
            ->causedBy(auth()->user())
            ->withProperties(['disk' => $disk, 'path' => $path])
            ->log('backup deleted');

        session()->flash('status', __('Backup deleted.'));
    }

    public function formatBytes(float $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = $bytes > 0 ? min((int) floor(log($bytes, 1024)), count($units) - 1) : 0;

        return round($bytes / (1024 ** $i), 1).' '.$units[$i];
    }
};
