<?php

use App\Models\AccountDataExport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function makeDataExport(array $overrides = []): AccountDataExport
{
    $user = User::factory()->create();

    return $user->dataExports()->create(array_merge([
        'token' => Str::random(64),
        'status' => 'processing',
    ], $overrides));
}

it('is processing only in the processing status', function () {
    expect(makeDataExport(['status' => 'processing'])->isProcessing())->toBeTrue();
    expect(makeDataExport(['status' => 'ready'])->isProcessing())->toBeFalse();
});

it('is ready only when status is ready and not yet expired', function () {
    expect(makeDataExport(['status' => 'ready', 'expires_at' => now()->addDay()])->isReady())->toBeTrue();
    expect(makeDataExport(['status' => 'ready', 'expires_at' => now()->subDay()])->isReady())->toBeFalse();
    expect(makeDataExport(['status' => 'processing', 'expires_at' => now()->addDay()])->isReady())->toBeFalse();
});

it('is expired only when status is ready and past the expiry date', function () {
    expect(makeDataExport(['status' => 'ready', 'expires_at' => now()->subDay()])->isExpired())->toBeTrue();
    expect(makeDataExport(['status' => 'ready', 'expires_at' => now()->addDay()])->isExpired())->toBeFalse();
});

it('validates the one-time download token', function () {
    $export = makeDataExport([
        'download_token' => 'secret-token',
        'download_token_expires_at' => now()->addMinute(),
    ]);

    expect($export->hasValidDownloadToken('secret-token'))->toBeTrue();
    expect($export->hasValidDownloadToken('wrong-token'))->toBeFalse();

    $export->update(['download_token_expires_at' => now()->subMinute()]);
    expect($export->hasValidDownloadToken('secret-token'))->toBeFalse();
});

it('records a download by bumping the count and timestamp', function () {
    $export = makeDataExport();

    $export->recordDownload();

    expect($export->fresh()->download_count)->toBe(1)
        ->and($export->fresh()->downloaded_at)->not->toBeNull();
});

it('consumes the download token', function () {
    $export = makeDataExport([
        'download_token' => 'secret-token',
        'download_token_expires_at' => now()->addMinute(),
    ]);

    $export->consumeDownloadToken();

    expect($export->fresh()->download_token)->toBeNull()
        ->and($export->fresh()->download_token_expires_at)->toBeNull();
});

it('builds the public download url from the token', function () {
    $export = makeDataExport(['token' => 'a-token']);

    expect($export->downloadUrl())->toBe(route('account.export.download', 'a-token'));
});
