<?php

namespace App\Modules\Billing\Listeners;

use App\Models\User;
use App\Modules\Billing\Services\Plans;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookHandled;

/**
 * Puts subscription changes that happen in Stripe (Checkout, Billing Portal,
 * failed renewals) on the user's activity timeline under "billing". Routine
 * renewals are skipped: an update is only logged when its status, plan or
 * cancellation changed. A no-op while the Activity module is off.
 */
class LogSubscriptionWebhook
{
    private const DESCRIPTIONS = [
        'customer.subscription.created' => 'subscription started',
        'customer.subscription.updated' => 'subscription changed',
        'customer.subscription.deleted' => 'subscription ended',
    ];

    private const MEANINGFUL_CHANGES = ['status', 'cancel_at_period_end', 'cancel_at', 'items'];

    public function __construct(private readonly Plans $plans) {}

    public function handle(WebhookHandled $event): void
    {
        $type = $event->payload['type'] ?? null;
        $object = $event->payload['data']['object'] ?? [];

        if (! isset(self::DESCRIPTIONS[$type])) {
            return;
        }

        $previous = array_keys($event->payload['data']['previous_attributes'] ?? []);

        if ($type === 'customer.subscription.updated' && array_intersect($previous, self::MEANINGFUL_CHANGES) === []) {
            return;
        }

        /** @var User|null $user */
        $user = Cashier::findBillable($object['customer'] ?? null);

        if ($user === null) {
            return;
        }

        activity('billing')
            ->performedOn($user)
            ->withProperties(array_filter([
                'plan' => $this->plans->nameForPrice($object['items']['data'][0]['price']['id'] ?? null),
                'status' => $object['status'] ?? null,
                'cancels_at_period_end' => ($object['cancel_at_period_end'] ?? false) ?: null,
            ], fn ($value) => $value !== null))
            ->log(self::DESCRIPTIONS[$type]);
    }
}
