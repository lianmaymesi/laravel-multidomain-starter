<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired by AccountDeletionService just before an account is anonymized, while
 * the user's real data is still there. Modules listen to clean up what they
 * own (e.g. Billing cancels subscriptions) — core never calls into them.
 *
 * It fires before anything is changed. A listener that throws stops the
 * deletion and puts the request back to "pending", so the queued job retries
 * (and the scheduler picks it up again) without anything anonymized half-way.
 */
class AccountDeleting
{
    use Dispatchable;

    public function __construct(public readonly User $user) {}
}
