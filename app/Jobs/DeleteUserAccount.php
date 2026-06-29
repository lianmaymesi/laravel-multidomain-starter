<?php

namespace App\Jobs;

use App\Models\AccountDeletionRequest;
use App\Services\AccountDeletionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteUserAccount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly AccountDeletionRequest $request) {}

    public function handle(AccountDeletionService $service): void
    {
        // Guard against already-processed or cancelled requests
        if (! $this->request->isPending()) {
            return;
        }

        $service->process($this->request);
    }
}
