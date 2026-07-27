<?php

use App\Models\AccountDataExport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function readyExport(array $overrides = []): AccountDataExport
{
    $user = User::factory()->create();

    return $user->dataExports()->create(array_merge([
        'token' => 'export-token',
        'status' => 'ready',
        'path' => 'exports/'.$user->id.'/export.zip',
        'expires_at' => now()->addDays(AccountDataExport::EXPORT_TTL_DAYS),
        'download_token' => 'download-token',
        'download_token_expires_at' => now()->addSeconds(60),
    ], $overrides));
}

it('downloads the export with a valid token and consumes it', function () {
    Storage::fake('local');
    $export = readyExport();
    Storage::disk('local')->put($export->path, 'zip-contents');

    $response = $this->get(route('account.export.download', ['token' => $export->token, 'dt' => 'download-token']));

    $response->assertSuccessful();
    expect($export->fresh()->download_token)->toBeNull();
});

it('rejects a missing export token', function () {
    $response = $this->get(route('account.export.download', ['token' => 'not-a-real-token', 'dt' => 'whatever']));

    $response->assertNotFound();
});

it('rejects a download when the export is not ready', function () {
    $export = readyExport(['status' => 'processing']);

    $response = $this->get(route('account.export.download', ['token' => $export->token, 'dt' => 'download-token']));

    $response->assertNotFound();
});

it('rejects a download with an invalid or expired one-time token', function () {
    $export = readyExport(['download_token_expires_at' => now()->subMinute()]);

    $response = $this->get(route('account.export.download', ['token' => $export->token, 'dt' => 'download-token']));

    $response->assertForbidden();
});

it('rejects a download when the export file no longer exists on disk', function () {
    Storage::fake('local');
    $export = readyExport();

    $response = $this->get(route('account.export.download', ['token' => $export->token, 'dt' => 'download-token']));

    $response->assertNotFound();
});
