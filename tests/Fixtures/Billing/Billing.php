<?php

namespace Tests\Fixtures\Billing;

use App\Models\User;
use Illuminate\Testing\TestResponse;
use Laravel\Cashier\Subscription;
use Tests\TestCase;

/** Shared setup for the Billing module tests. */
class Billing
{
    public const WEBHOOK_SECRET = 'whsec_test';

    /** Stripe keys, webhook secret and one plan ("pro") for sale. */
    public static function configure(): void
    {
        config([
            'cashier.key' => 'pk_test_123',
            'cashier.secret' => 'sk_test_123',
            'cashier.webhook.secret' => self::WEBHOOK_SECRET,
            'billing.plans.starter.price' => null,
            'billing.plans.pro.price' => 'price_pro',
        ]);
    }

    /** A local subscription row, as the created-webhook would have written it. */
    public static function subscribe(User $user, array $attributes = []): Subscription
    {
        if (! $user->hasStripeId()) {
            $user->forceFill(['stripe_id' => 'cus_'.$user->id])->save();
        }

        $subscription = $user->subscriptions()->create(array_merge([
            'type' => 'default',
            'stripe_id' => 'sub_'.$user->id.'_'.uniqid(),
            'stripe_status' => 'active',
            'stripe_price' => 'price_pro',
            'quantity' => 1,
        ], $attributes));

        $subscription->items()->create([
            'stripe_id' => 'si_'.$subscription->id,
            'stripe_product' => 'prod_pro',
            'stripe_price' => $subscription->stripe_price,
            'quantity' => 1,
        ]);

        return $subscription;
    }

    /** Stripe's subscription object, as returned by the API or sent in a webhook. */
    public static function stripeSubscription(string $id, string $customer, array $overrides = []): array
    {
        return array_replace_recursive([
            'id' => $id,
            'object' => 'subscription',
            'customer' => $customer,
            'status' => 'active',
            'cancel_at_period_end' => false,
            'trial_end' => null,
            'items' => [
                'object' => 'list',
                'data' => [[
                    'id' => 'si_'.$id,
                    'object' => 'subscription_item',
                    'quantity' => 1,
                    'current_period_end' => now()->addMonth()->getTimestamp(),
                    'price' => ['id' => 'price_pro', 'object' => 'price', 'product' => 'prod_pro'],
                ]],
            ],
        ], $overrides);
    }

    /** POST a webhook to /stripe/webhook with a valid (or the given) signature. */
    public static function postWebhook(TestCase $test, array $payload, ?string $secret = self::WEBHOOK_SECRET): TestResponse
    {
        $json = json_encode($payload);
        $timestamp = time();
        $signature = 't='.$timestamp.',v1='.hash_hmac('sha256', "{$timestamp}.{$json}", (string) $secret);

        return $test->call('POST', '/stripe/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_STRIPE_SIGNATURE' => $signature,
        ], $json);
    }
}
