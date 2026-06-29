<?php

namespace App\Http\Controllers;

use App\Models\AccountDataExport;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountExportController extends Controller
{
    public function __invoke(string $token): StreamedResponse
    {
        $export = AccountDataExport::where('token', $token)
            ->where('expires_at', '>', now())
            ->firstOrFail();

        $disk = Storage::disk('local');

        abort_unless($disk->exists($export->path), 404, 'Export file not found.');

        return $disk->download(
            $export->path,
            'my-data-export.zip',
            ['Content-Type' => 'application/zip']
        );
    }
}
