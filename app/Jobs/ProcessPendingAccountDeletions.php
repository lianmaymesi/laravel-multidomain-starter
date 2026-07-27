<?php

namespace App\Jobs;

use App\Models\AccountDeletionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessPendingAccountDeletions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        AccountDeletionRequest::query()
            ->where('status', 'pending')
            ->where('scheduled_at', '<=', now())
            ->each(function (AccountDeletionRequest $request) {
                DeleteUserAccount::dispatch($request);
            });
    }
}
