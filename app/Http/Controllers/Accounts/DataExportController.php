<?php

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\AccountDataExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DataExportController extends Controller
{
    public function __invoke(Request $request, string $token): BinaryFileResponse
    {
        $export = AccountDataExport::where('token', '=', $token)->firstOrFail();
        $dt = (string) $request->query('dt', '');

        if (! $export->isReady()) {
            abort(404, 'Export not available.');
        }

        if (! $export->hasValidDownloadToken($dt)) {
            abort(403, 'Invalid or expired download link. Please re-authenticate from your account.');
        }

        $fullPath = Storage::disk('local')->path($export->path);

        if (! file_exists($fullPath)) {
            abort(404, 'Export file not found.');
        }

        $export->consumeDownloadToken();

        return response()->download($fullPath, 'my-data-export.zip', [
            'Content-Type' => 'application/zip',
        ]);
    }
}
