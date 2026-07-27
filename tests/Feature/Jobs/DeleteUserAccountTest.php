<?php

use App\Jobs\DeleteUserAccount;
use App\Models\User;
use App\Services\AccountDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('anonymizes the user and marks the request completed', function () {
    $user = User::factory()->create(['name' => 'Real Name', 'email' => 'real@example.com']);

    $request = $user->deletionRequest()->create([
        'requested_at' => now(),
        'scheduled_at' => now()->subMinute(),
        'status' => 'pending',
    ]);

    (new DeleteUserAccount($request))->handle(app(AccountDeletionService::class));

    $user->refresh();
    $request->refresh();

    expect($user->name)->toBe('Deleted User')
        ->and($user->email)->toBe("deleted_{$user->id}@deleted.invalid")
        ->and($request->status)->toBe('completed');
});

it('does nothing when the request is no longer pending', function () {
    $user = User::factory()->create(['name' => 'Real Name']);

    $request = $user->deletionRequest()->create([
        'requested_at' => now(),
        'scheduled_at' => now()->subMinute(),
        'status' => 'cancelled',
    ]);

    (new DeleteUserAccount($request))->handle(app(AccountDeletionService::class));

    expect($user->fresh()->name)->toBe('Real Name');
});
