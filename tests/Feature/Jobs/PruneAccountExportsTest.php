<?php

use App\Jobs\PruneAccountExports;
use App\Models\AccountDataExport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('deletes expired exports and their files', function () {
    Storage::fake('local');

    $user = User::factory()->create();

    $expired = $user->dataExports()->create([
        'token' => Str::random(64),
        'status' => 'ready',
        'path' => "exports/{$user->id}/expired.zip",
        'expires_at' => now()->subDay(),
    ]);
    Storage::disk('local')->put($expired->path, 'content');

    $active = $user->dataExports()->create([
        'token' => Str::random(64),
        'status' => 'ready',
        'path' => "exports/{$user->id}/active.zip",
        'expires_at' => now()->addDay(),
    ]);
    Storage::disk('local')->put($active->path, 'content');

    (new PruneAccountExports)->handle();

    expect(AccountDataExport::find($expired->id))->toBeNull()
        ->and(AccountDataExport::find($active->id))->not->toBeNull();

    Storage::disk('local')->assertMissing($expired->path);
    Storage::disk('local')->assertExists($active->path);
});
