<?php

namespace App\Modules\Billing\Listeners;

use App\Events\AccountDeleting;
use Laravel\Cashier\Subscription;

/**
 * A deleted account must stop being charged. Cancels every renewing
 * subscription in Stripe right away (no proration refund — same as cancelling in the
 * Billing Portal). A Stripe error throws, which stops the deletion (the
 * request goes back to pending and is retried) rather than anonymizing a
 * still-paying account.
 */
class CancelSubscriptionsOnAccountDeletion
{
    public function handle(AccountDeleting $event): void
    {
        // No ends_at = still renews. One already cancelled at period end
        // (grace period) won't charge again, so it's left to run out.
        Subscription::query()
            ->whereBelongsTo($event->user, 'owner')
            ->whereNull('ends_at')
            ->get()
            ->each(function (Subscription $subscription) use ($event) {
                $subscription->cancelNow();

                activity('billing')
                    ->performedOn($event->user)
                    ->withProperties(['subscription' => $subscription->getAttribute('stripe_id')])
                    ->log('subscription cancelled on account deletion');
            });
    }
}
