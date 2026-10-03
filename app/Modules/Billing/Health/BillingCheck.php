<?php

namespace App\Modules\Billing\Health;

use App\Modules\Billing\Services\Plans;
use App\Support\Health\Check;
use App\Support\Health\Result;
use Laravel\Cashier\Subscription;

/**
 * Contributed by the Billing module. Checks configuration only — never calls
 * Stripe — so /health stays fast and works offline. Missing keys or webhook
 * secret fail (checkout or subscription sync is broken); past-due
 * subscriptions are a warning worth a look.
 */
class BillingCheck implements Check
{
    public function __construct(private readonly Plans $plans) {}

    public function name(): string
    {
        return 'billing';
    }

    public function label(): string
    {
        return 'Billing';
    }

    public function run(): Result
    {
        $missing = array_keys(array_filter([
            'STRIPE_KEY' => blank(config('cashier.key')),
            'STRIPE_SECRET' => blank(config('cashier.secret')),
            'STRIPE_WEBHOOK_SECRET' => blank(config('cashier.webhook.secret')),
        ]));

        if ($missing !== []) {
            return Result::failed('Not set: '.implode(', ', $missing).'.', ['missing' => $missing]);
        }

        $plans = $this->plans->all()->count();

        if ($plans === 0) {
            return Result::failed('No plan has a Stripe price id (BILLING_*_PRICE).', ['plans' => 0]);
        }

        $pastDue = Subscription::query()->where('stripe_status', 'past_due')->count();

        return $pastDue > 0
            ? Result::warning(trans_choice(':count subscription is past due.|:count subscriptions are past due.', $pastDue), ['plans' => $plans, 'past_due' => $pastDue])
            : Result::ok(trans_choice(':count plan for sale.|:count plans for sale.', $plans), ['plans' => $plans, 'past_due' => 0]);
    }
}
