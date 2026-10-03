<?php

use App\Models\User;
use App\Modules\Activity\Models\Activity;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Fixtures\Billing\Billing;
use Tests\Fixtures\Billing\FakeStripe;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->enableModules('billing');
    $this->seed(RolePermissionSeeder::class);
    Billing::configure();

    $this->stripe = FakeStripe::install();
});

afterEach(fn () => FakeStripe::uninstall());

it('blocks staff without billing.view', function () {
    Livewire::actingAs(staffUser())->test('billing::index')->assertForbidden();
});

it('lists subscriptions with stats, search and status filter', function () {
    $alice = User::factory()->create(['name' => 'Alice Payer']);
    $bob = User::factory()->create(['name' => 'Bob Late']);
    Billing::subscribe($alice);
    Billing::subscribe($bob, ['stripe_status' => 'past_due']);

    $page = Livewire::actingAs(adminActor())->test('billing::index');

    expect($page->instance()->stats())->toMatchArray(['active' => 1, 'past_due' => 1, 'trialing' => 0, 'grace' => 0]);

    $page->assertSee('Alice Payer')->assertSee('Bob Late')
        ->set('status', 'past_due')->assertSee('Bob Late')->assertDontSee('Alice Payer')
        ->set('status', '')->set('search', 'alice')->assertSee('Alice Payer')->assertDontSee('Bob Late');
});

it('keeps cancel and resume to billing.manage (Super Admin)', function () {
    $subscription = Billing::subscribe(User::factory()->create());

    Livewire::actingAs(adminActor())
        ->test('billing::index')
        ->assertDontSee('End now')
        ->call('cancelNow', $subscription->id)
        ->assertForbidden();

    expect($this->stripe->requests)->toBe([])
        ->and($subscription->fresh()->ended())->toBeFalse();
});

it('lets a Super Admin end a subscription now, with an audit entry', function () {
    $customer = User::factory()->create();
    $subscription = Billing::subscribe($customer);
    $actor = superAdminActor();

    $this->stripe->respond('delete', '#^/v1/subscriptions/'.$subscription->stripe_id.'$#',
        Billing::stripeSubscription($subscription->stripe_id, $customer->stripe_id, ['status' => 'canceled']));

    Livewire::actingAs($actor)
        ->test('billing::index')
        ->call('cancelNow', $subscription->id)
        ->assertSee('Subscription ended immediately.');

    $activity = Activity::where('log_name', 'billing')->sole();

    expect($subscription->fresh()->ended())->toBeTrue()
        ->and($this->stripe->sent('delete', '#/v1/subscriptions/#'))->toBeTrue()
        ->and($activity->description)->toBe('subscription ended by staff')
        ->and($activity->causer_id)->toBe($actor->id)
        ->and($activity->subject_id)->toBe($customer->id);
});

it('lets a Super Admin resume a subscription in its grace period', function () {
    $customer = User::factory()->create();
    $subscription = Billing::subscribe($customer, ['ends_at' => now()->addWeek()]);

    $this->stripe->respond('post', '#^/v1/subscriptions/'.$subscription->stripe_id.'$#',
        Billing::stripeSubscription($subscription->stripe_id, $customer->stripe_id));

    Livewire::actingAs(superAdminActor())
        ->test('billing::index')
        ->call('resume', $subscription->id)
        ->assertSee('Subscription resumed.');

    expect($subscription->fresh()->ends_at)->toBeNull();
});
