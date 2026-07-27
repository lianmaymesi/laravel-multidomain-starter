<?php

use App\Jobs\ExportUserData;
use App\Models\User;
use App\Notifications\AccountDataExportReady;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('generates a zip export, marks it ready, and notifies the user', function () {
    Storage::fake('local');
    Notification::fake();

    $user = User::factory()->create();

    $export = $user->dataExports()->create([
        'token' => Str::random(64),
        'status' => 'processing',
    ]);

    (new ExportUserData($export))->handle();

    $export->refresh();

    expect($export->status)->toBe('ready')
        ->and($export->path)->not->toBeNull();

    Storage::disk('local')->assertExists($export->path);

    Notification::assertSentTo($user, AccountDataExportReady::class);
});
