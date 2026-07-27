<?php

use App\Models\AccountDeletionRequest;
use App\Models\User;
use App\Notifications\AccountDeletionCancelled;
use App\Notifications\AccountDeletionRequested;
use App\Services\AccountDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('creates a pending deletion request, revokes sessions, and notifies the user', function () {
    Notification::fake();

    $user = User::factory()->create();
    DB::table('sessions')->insert(['id' => 'sess-1', 'user_id' => $user->id, 'payload' => '', 'last_activity' => time()]);

    $request = app(AccountDeletionService::class)->request($user);

    expect($request->status)->toBe('pending')
        ->and($request->scheduled_at->isSameDay(now()->addDays(AccountDeletionRequest::GRACE_PERIOD_DAYS)))->toBeTrue()
        ->and(DB::table('sessions')->where('user_id', $user->id)->count())->toBe(0);

    Notification::assertSentTo($user, AccountDeletionRequested::class);
});

it('refuses to create a second deletion request while one is active', function () {
    $user = User::factory()->create();
    app(AccountDeletionService::class)->request($user);

    app(AccountDeletionService::class)->request($user);
})->throws(LogicException::class);

it('cancels a cancellable request and notifies the user', function () {
    Notification::fake();

    $user = User::factory()->create();
    $request = app(AccountDeletionService::class)->request($user);

    app(AccountDeletionService::class)->cancel($request);

    expect($request->fresh()->status)->toBe('cancelled');
    Notification::assertSentTo($user, AccountDeletionCancelled::class);
});

it('refuses to cancel a request that is no longer cancellable', function () {
    $user = User::factory()->create();

    $request = $user->deletionRequest()->create([
        'requested_at' => now(),
        'scheduled_at' => now()->subMinute(),
        'status' => 'processing',
    ]);

    app(AccountDeletionService::class)->cancel($request);
})->throws(LogicException::class);

it('processes a request by anonymizing the user and clearing their otps', function () {
    $user = User::factory()->create();
    $user->otps()->create([
        'type' => 'phone_verification',
        'code' => '123456',
        'expires_at' => now()->addMinutes(10),
    ]);

    $request = $user->deletionRequest()->create([
        'requested_at' => now(),
        'scheduled_at' => now()->subMinute(),
        'status' => 'pending',
    ]);

    app(AccountDeletionService::class)->process($request);

    expect($request->fresh()->status)->toBe('completed')
        ->and($user->otps()->count())->toBe(0)
        ->and($user->fresh()->name)->toBe('Deleted User');
});
