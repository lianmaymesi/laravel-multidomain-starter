<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Fixtures\Billing\Billing;
use Tests\Fixtures\Billing\FakeStripe;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->enableModules('billing');
    Billing::configure();

    $this->stripe = FakeStripe::install();
    $this->user = User::factory()->create();
});

afterEach(fn () => FakeStripe::uninstall());

it('says billing is unavailable until Stripe and a plan are configured', function () {
    config(['cashier.secret' => null]);

    Livewire::actingAs($this->user)
        ->test('billing::account')
        ->assertSeeHtml('data-not-ready')
        ->assertDontSee('Subscribe');
});

it('offers only plans that have a Stripe price', function () {
    Livewire::actingAs($this->user)
        ->test('billing::account')
        ->assertSee('Pro')
        ->assertSee('$29 / month')
        ->assertDontSee('For individuals getting started.')
        ->assertDontSeeHtml('wire:key="plan-starter"');
});

it('sends a new subscriber to Stripe Checkout', function () {
    $this->stripe
        ->respond('post', '#^/v1/customers$#', ['id' => 'cus_new', 'object' => 'customer'])
        ->respond('post', '#^/v1/checkout/sessions$#', ['id' => 'cs_test', 'object' => 'checkout.session', 'url' => 'https://checkout.stripe.com/c/pay/cs_test']);

    Livewire::actingAs($this->user)
        ->test('billing::account')
        ->call('subscribe', 'pro')
        ->assertRedirect('https://checkout.stripe.com/c/pay/cs_test');

    $session = collect($this->stripe->requests)->firstWhere('path', '/v1/checkout/sessions');

    expect($this->user->fresh()->stripe_id)->toBe('cus_new')
        ->and($session['params']['line_items'][0]['price'])->toBe('price_pro')
        ->and($session['params']['success_url'])->toContain('checkout=success');
});

it('refuses an unknown or unpriced plan', function () {
    Livewire::actingAs($this->user)->test('billing::account')->call('subscribe', 'starter')->assertNotFound();
    Livewire::actingAs($this->user)->test('billing::account')->call('subscribe', 'enterprise')->assertNotFound();

    expect($this->stripe->requests)->toBe([]);
});

it('never starts a second checkout for an existing subscriber', function () {
    Billing::subscribe($this->user);

    Livewire::actingAs($this->user)
        ->test('billing::account')
        ->call('subscribe', 'pro')
        ->assertNoRedirect();

    expect($this->stripe->requests)->toBe([]);
});

it('shows a pending notice after returning from checkout', function () {
    $this->actingAs($this->user)
        ->withoutVite()
        ->get(route('account.billing', ['checkout' => 'success']))
        ->assertOk()
        ->assertSee('Payment received');
});

it('shows the current plan and opens the Stripe Billing Portal', function () {
    Billing::subscribe($this->user);
    $this->stripe->respond('post', '#^/v1/billing_portal/sessions$#', ['id' => 'bps_1', 'object' => 'billing_portal.session', 'url' => 'https://billing.stripe.com/p/session/test']);

    Livewire::actingAs($this->user)
        ->test('billing::account')
        ->assertSee('Pro')
        ->assertSee('Active')
        ->call('manage')
        ->assertRedirect('https://billing.stripe.com/p/session/test');
});

it('cancels at period end and resumes', function () {
    $subscription = Billing::subscribe($this->user);
    $periodEnd = now()->addDays(12)->startOfSecond();

    $this->stripe
        ->respond('post', '#^/v1/subscriptions/'.$subscription->stripe_id.'$#', Billing::stripeSubscription($subscription->stripe_id, $this->user->stripe_id))
        ->respond('get', '#^/v1/subscription_items/#', ['id' => 'si_x', 'object' => 'subscription_item', 'current_period_end' => $periodEnd->getTimestamp()]);

    Livewire::actingAs($this->user)
        ->test('billing::account')
        ->call('cancel')
        ->assertSee('Cancelled')
        ->assertSee('Resume subscription');

    expect($subscription->fresh()->onGracePeriod())->toBeTrue()
        ->and($subscription->fresh()->ends_at->equalTo($periodEnd))->toBeTrue();

    Livewire::actingAs($this->user)
        ->test('billing::account')
        ->call('resume')
        ->assertSee('Your subscription is active again.');

    expect($subscription->fresh()->ends_at)->toBeNull();
});

it('does not let a user cancel or resume without a subscription', function () {
    Livewire::actingAs($this->user)->test('billing::account')->call('cancel')->assertNotFound();
    Livewire::actingAs($this->user)->test('billing::account')->call('resume')->assertNotFound();
});
