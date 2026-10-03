<?php

use App\Models\User;
use App\Modules\Activity\Models\Activity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Cashier\Subscription;
use Tests\Fixtures\Billing\Billing;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->enableModules('billing');
    Billing::configure();

    $this->customer = User::factory()->create(['stripe_id' => 'cus_webhook']);
});

function subscriptionEvent(string $type, array $object, array $previous = []): array
{
    return [
        'id' => 'evt_'.uniqid(),
        'type' => $type,
        'data' => array_filter(['object' => $object, 'previous_attributes' => $previous ?: null]),
    ];
}

it('refuses every webhook while no webhook secret is configured', function () {
    config(['cashier.webhook.secret' => null]);

    Billing::postWebhook($this, subscriptionEvent('customer.subscription.created', Billing::stripeSubscription('sub_1', 'cus_webhook')), secret: '')
        ->assertStatus(503);

    expect(Subscription::count())->toBe(0);
});

it('rejects a webhook with a bad signature', function () {
    Billing::postWebhook($this, subscriptionEvent('customer.subscription.created', Billing::stripeSubscription('sub_1', 'cus_webhook')), secret: 'whsec_wrong')
        ->assertForbidden();

    expect(Subscription::count())->toBe(0);
});

it('creates the local subscription from a verified created webhook and logs it', function () {
    Billing::postWebhook($this, subscriptionEvent('customer.subscription.created', Billing::stripeSubscription('sub_1', 'cus_webhook')))
        ->assertOk();

    $subscription = $this->customer->subscription('default');

    expect($subscription)->not->toBeNull()
        ->and($subscription->stripe_id)->toBe('sub_1')
        ->and($subscription->stripe_price)->toBe('price_pro')
        ->and($this->customer->subscribed())->toBeTrue();

    $activity = Activity::where('log_name', 'billing')->where('subject_id', $this->customer->id)->sole();

    expect($activity->description)->toBe('subscription started')
        ->and($activity->properties['plan'])->toBe('Pro');
});

it('follows the subscription through past due, cancellation and its end', function () {
    Billing::subscribe($this->customer, ['stripe_id' => 'sub_1']);

    Billing::postWebhook($this, subscriptionEvent('customer.subscription.updated',
        Billing::stripeSubscription('sub_1', 'cus_webhook', ['status' => 'past_due']), ['status' => 'active']))->assertOk();

    expect($this->customer->fresh()->subscription()->pastDue())->toBeTrue();

    Billing::postWebhook($this, subscriptionEvent('customer.subscription.deleted',
        Billing::stripeSubscription('sub_1', 'cus_webhook', ['status' => 'canceled'])))->assertOk();

    $subscription = $this->customer->fresh()->subscription();

    expect($subscription->ended())->toBeTrue()
        ->and($this->customer->fresh()->subscribed())->toBeFalse()
        ->and(Activity::where('log_name', 'billing')->pluck('description')->all())
        ->toBe(['subscription changed', 'subscription ended']);
});

it('does not log routine renewals', function () {
    Billing::subscribe($this->customer, ['stripe_id' => 'sub_1']);

    Billing::postWebhook($this, subscriptionEvent('customer.subscription.updated',
        Billing::stripeSubscription('sub_1', 'cus_webhook'), ['latest_invoice' => 'in_old']))->assertOk();

    expect(Activity::where('log_name', 'billing')->exists())->toBeFalse();
});
