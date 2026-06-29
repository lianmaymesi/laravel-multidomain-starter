<?php

namespace App\Services;

use App\Models\AccountDeletionRequest;
use App\Models\User;
use App\Notifications\AccountDeletionCancelled;
use App\Notifications\AccountDeletionRequested;
use Illuminate\Support\Facades\DB;
use LogicException;

class AccountDeletionService
{
    public function request(User $user): AccountDeletionRequest
    {
        if ($user->activeDeletionRequest()) {
            throw new LogicException('An active deletion request already exists.');
        }

        $now = now();

        $request = $user->deletionRequest()->create([
            'requested_at' => $now,
            'scheduled_at' => $now->copy()->addDays(AccountDeletionRequest::GRACE_PERIOD_DAYS),
            'status'       => 'pending',
        ]);

        $user->notify(new AccountDeletionRequested($request));

        return $request;
    }

    public function cancel(AccountDeletionRequest $request): void
    {
        if (! $request->isCancellable()) {
            throw new LogicException('This deletion request cannot be cancelled.');
        }

        $request->update(['status' => 'cancelled']);
        $request->user->notify(new AccountDeletionCancelled);
    }

    public function process(AccountDeletionRequest $request): void
    {
        $request->update(['status' => 'processing']);

        $user = $request->user;

        // Revoke all active sessions
        DB::table('sessions')->where('user_id', $user->id)->delete();

        // Delete OTPs
        $user->otps()->delete();

        // Anonymize user — keeps the row for audit/FK integrity
        $user->anonymize();

        $request->update(['status' => 'completed']);
    }
}
