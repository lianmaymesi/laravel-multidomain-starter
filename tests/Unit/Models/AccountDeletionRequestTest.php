<?php

use App\Models\AccountDeletionRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeDeletionRequest(array $overrides = []): AccountDeletionRequest
{
    $user = User::factory()->create();

    return $user->deletionRequest()->create(array_merge([
        'requested_at' => now(),
        'scheduled_at' => now()->addDays(AccountDeletionRequest::GRACE_PERIOD_DAYS),
        'status' => 'pending',
    ], $overrides));
}

it('is pending only when the status is pending', function () {
    expect(makeDeletionRequest(['status' => 'pending'])->isPending())->toBeTrue();
    expect(makeDeletionRequest(['status' => 'processing'])->isPending())->toBeFalse();
});

it('is cancellable only while pending and the scheduled date is in the future', function () {
    expect(makeDeletionRequest(['status' => 'pending', 'scheduled_at' => now()->addDay()])->isCancellable())->toBeTrue();
    expect(makeDeletionRequest(['status' => 'pending', 'scheduled_at' => now()->subDay()])->isCancellable())->toBeFalse();
    expect(makeDeletionRequest(['status' => 'processing', 'scheduled_at' => now()->addDay()])->isCancellable())->toBeFalse();
});

it('calculates the days remaining until deletion, floored at zero', function () {
    expect(makeDeletionRequest(['scheduled_at' => now()->addDays(5)])->daysRemaining())->toBeGreaterThanOrEqual(4);
    expect(makeDeletionRequest(['scheduled_at' => now()->subDays(5)])->daysRemaining())->toBe(0);
});
