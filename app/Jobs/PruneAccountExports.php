<?php

namespace App\Jobs;

use App\Models\AccountDataExport;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class PruneAccountExports implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        AccountDataExport::where('expires_at', '<', now())
            ->each(function (AccountDataExport $export) {
                Storage::disk('local')->delete($export->path);
                $export->delete();
            });
    }
}
