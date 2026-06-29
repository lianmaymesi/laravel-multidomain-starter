<?php

namespace App\Jobs;

use App\Models\AccountDataExport;
use App\Notifications\AccountDataExportReady;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ExportUserData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(public readonly AccountDataExport $export) {}

    public function handle(): void
    {
        $export  = $this->export->fresh();
        $user    = $export->user;
        $token   = $export->token;
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "jrb_export_{$user->id}_{$token}";

        mkdir($tempDir, 0755, true);

        try {
            $this->writeProfileCsv($user, $tempDir);
            $this->writeSessionsCsv($user, $tempDir);

            $zipTempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "{$token}.zip";
            $this->createZip($tempDir, $zipTempPath);

            $storagePath = "exports/{$user->id}/{$token}.zip";
            Storage::disk('local')->put($storagePath, file_get_contents($zipTempPath));

            $export->update([
                'status'     => 'ready',
                'path'       => $storagePath,
                'expires_at' => now()->addDays(AccountDataExport::EXPORT_TTL_DAYS),
            ]);

            $user->notify(new AccountDataExportReady($export->fresh()));

        } finally {
            $this->cleanDir($tempDir);
            if (isset($zipTempPath) && file_exists($zipTempPath)) {
                unlink($zipTempPath);
            }
        }
    }

    private function writeProfileCsv($user, string $dir): void
    {
        $path = $dir . DIRECTORY_SEPARATOR . 'profile.csv';
        $file = fopen($path, 'w');

        fputcsv($file, ['Field', 'Value']);
        fputcsv($file, ['ID', $user->id]);
        fputcsv($file, ['Name', $user->name]);
        fputcsv($file, ['Email', $user->email]);
        fputcsv($file, ['Phone', $user->phone ? $user->country_code . $user->phone : '']);
        fputcsv($file, ['Privilege', $user->privilege]);
        fputcsv($file, ['Email Verified At', $user->email_verified_at?->toDateTimeString() ?? '']);
        fputcsv($file, ['Phone Verified At', $user->phone_verified_at?->toDateTimeString() ?? '']);
        fputcsv($file, ['2FA Enabled', $user->hasTwoFactorEnabled() ? 'Yes' : 'No']);
        fputcsv($file, ['Account Created At', $user->created_at?->toDateTimeString()]);
        fputcsv($file, ['Last Updated At', $user->updated_at?->toDateTimeString()]);

        fclose($file);
    }

    private function writeSessionsCsv($user, string $dir): void
    {
        $path     = $dir . DIRECTORY_SEPARATOR . 'sessions.csv';
        $file     = fopen($path, 'w');
        $sessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get();

        fputcsv($file, ['IP Address', 'User Agent', 'Last Active']);

        foreach ($sessions as $session) {
            fputcsv($file, [
                $session->ip_address,
                $session->user_agent,
                date('Y-m-d H:i:s', $session->last_activity),
            ]);
        }

        fclose($file);
    }

    private function createZip(string $sourceDir, string $zipPath): void
    {
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach (glob($sourceDir . DIRECTORY_SEPARATOR . '*') as $file) {
            if (is_file($file)) {
                $zip->addFile($file, basename($file));
            }
        }

        $zip->close();
    }

    private function cleanDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (glob($dir . DIRECTORY_SEPARATOR . '*') as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        rmdir($dir);
    }
}
