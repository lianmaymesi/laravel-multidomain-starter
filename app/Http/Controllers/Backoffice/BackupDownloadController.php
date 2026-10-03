<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Support\Backup\BackupInventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a backup zip from its destination disk. A backup holds the whole
 * database, so: backups.manage only, the path must be one BackupInventory
 * lists (no arbitrary files from the disk), and every download is logged.
 */
class BackupDownloadController extends Controller
{
    public function __invoke(Request $request, BackupInventory $inventory): StreamedResponse
    {
        abort_unless(Gate::allows('backups.manage'), 403);

        $disk = (string) $request->query('disk');
        $path = (string) $request->query('path');

        $backup = $inventory->find($disk, $path);

        abort_if($backup === null, 404);

        activity('backups')
            ->causedBy($request->user())
            ->withProperties(['disk' => $disk, 'path' => $path, 'ip' => $request->ip()])
            ->log('backup downloaded');

        return response()->streamDownload(function () use ($backup) {
            $stream = $backup->stream();
            fpassthru($stream);
            fclose($stream);
        }, basename($path), [
            'Content-Type' => 'application/zip',
            'Content-Length' => (string) (int) $backup->sizeInBytes(),
            'Cache-Control' => 'no-store',
        ]);
    }
}
