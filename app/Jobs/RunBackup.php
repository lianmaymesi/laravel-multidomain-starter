<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Artisan;

/**
 * "Back up now" from Backoffice → Backups. Runs `backup:run` on the queue so
 * the request doesn't wait for a dump + zip; failures still go through
 * spatie's notifications like a scheduled run.
 */
class RunBackup implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public const SCOPES = ['full', 'db', 'files'];

    // spatie retries itself (config backup.tries); a second job run would
    // just take a second backup.
    public int $tries = 1;

    public int $timeout = 3600;

    // One manual backup at a time.
    public int $uniqueFor = 3600;

    public function __construct(
        public readonly string $scope = 'full',
        public readonly ?int $requestedBy = null,
    ) {}

    public function handle(): void
    {
        $options = match ($this->scope) {
            'db' => ['--only-db' => true],
            'files' => ['--only-files' => true],
            default => [],
        };

        $exitCode = Artisan::call('backup:run', $options);

        activity('backups')
            ->causedBy($this->requestedBy ? User::find($this->requestedBy) : null)
            ->withProperties(['scope' => $this->scope, 'succeeded' => $exitCode === 0])
            ->log($exitCode === 0 ? 'backup completed' : 'backup failed');
    }
}
