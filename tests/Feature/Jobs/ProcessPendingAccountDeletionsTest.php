<?php

use App\Jobs\DeleteUserAccount;
use App\Jobs\ProcessPendingAccountDeletions;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

it('dispatches deletion jobs only for due pending requests', function () {
    Queue::fake();

    $due = User::factory()->create();
    $dueRequest = $due->deletionRequest()->create([
        'requested_at' => now()->subDays(31),
        'scheduled_at' => now()->subMinute(),
        'status' => 'pending',
    ]);

    $notYetDue = User::factory()->create();
    $notYetDue->deletionRequest()->create([
        'requested_at' => now(),
        'scheduled_at' => now()->addDays(29),
        'status' => 'pending',
    ]);

    $cancelled = User::factory()->create();
    $cancelled->deletionRequest()->create([
        'requested_at' => now()->subDays(31),
        'scheduled_at' => now()->subMinute(),
        'status' => 'cancelled',
    ]);

    (new ProcessPendingAccountDeletions)->handle();

    Queue::assertPushed(DeleteUserAccount::class, 1);
    Queue::assertPushed(DeleteUserAccount::class, fn ($job) => $job->request->is($dueRequest));
});
