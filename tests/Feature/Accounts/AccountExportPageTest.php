<?php

use App\Jobs\ExportUserData;
use App\Models\AccountDataExport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('queues a data export request', function () {
    Queue::fake();

    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::accounts.export')
        ->call('exportData');

    expect($user->dataExports()->where('status', 'processing')->exists())->toBeTrue();

    Queue::assertPushed(ExportUserData::class);
});

it('confirms a download with the correct password and generates a one-time token', function () {
    $user = User::factory()->create(['password' => Hash::make('correct-password')]);

    $export = $user->dataExports()->create([
        'token' => Str::random(64),
        'status' => 'ready',
        'path' => 'exports/whatever.zip',
        'expires_at' => now()->addDays(AccountDataExport::EXPORT_TTL_DAYS),
    ]);

    Livewire::actingAs($user)
        ->test('pages::accounts.export')
        ->call('initiateDownload', $export->id)
        ->set('downloadPassword', 'correct-password')
        ->call('confirmDownload')
        ->assertSet('downloadExportId', null);

    $export->refresh();

    expect($export->download_token)->not->toBeNull()
        ->and($export->download_count)->toBe(1);
});

it('rejects a download confirmation with an incorrect password', function () {
    $user = User::factory()->create(['password' => Hash::make('correct-password')]);

    $export = $user->dataExports()->create([
        'token' => Str::random(64),
        'status' => 'ready',
        'path' => 'exports/whatever.zip',
        'expires_at' => now()->addDays(AccountDataExport::EXPORT_TTL_DAYS),
    ]);

    Livewire::actingAs($user)
        ->test('pages::accounts.export')
        ->call('initiateDownload', $export->id)
        ->set('downloadPassword', 'wrong-password')
        ->call('confirmDownload')
        ->assertHasErrors('downloadPassword');

    expect($export->fresh()->download_token)->toBeNull();
});

it('blocks downloads once the daily limit is reached', function () {
    $user = User::factory()->create(['password' => Hash::make('correct-password')]);

    $export = $user->dataExports()->create([
        'token' => Str::random(64),
        'status' => 'ready',
        'path' => 'exports/whatever.zip',
        'expires_at' => now()->addDays(AccountDataExport::EXPORT_TTL_DAYS),
    ]);

    RateLimiter::hit(AccountDataExport::DOWNLOAD_LIMITER_KEY.$user->id, 60 * 60 * 24);
    RateLimiter::hit(AccountDataExport::DOWNLOAD_LIMITER_KEY.$user->id, 60 * 60 * 24);
    RateLimiter::hit(AccountDataExport::DOWNLOAD_LIMITER_KEY.$user->id, 60 * 60 * 24);

    Livewire::actingAs($user)
        ->test('pages::accounts.export')
        ->call('initiateDownload', $export->id)
        ->set('downloadPassword', 'correct-password')
        ->call('confirmDownload')
        ->assertHasErrors('downloadPassword');
});
