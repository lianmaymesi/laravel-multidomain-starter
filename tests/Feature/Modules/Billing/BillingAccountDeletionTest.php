<?php

use App\Models\AccountDeletionRequest;
use App\Models\User;
use App\Services\AccountDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Exception\ApiErrorException;
use Tests\Fixtures\Billing\Billing;
use Tests\Fixtures\Billing\FakeStripe;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->enableModules('billing');
    Billing::configure();

    $this->stripe = FakeStripe::install();
});

afterEach(fn () => FakeStripe::uninstall());

function dueDeletionRequest(User $user): AccountDeletionRequest
{
    return $user->deletionRequest()->create([
        'requested_at' => now()->subDays(31),
        'scheduled_at' => now()->subDay(),
        'status' => 'pending',
    ]);
}

it('cancels renewing subscriptions in Stripe before anonymizing the account', function () {
    $user = User::factory()->create(['pm_type' => 'visa', 'pm_last_four' => '4242']);
    $renewing = Billing::subscribe($user);
    $alreadyCancelling = Billing::subscribe($user, ['ends_at' => now()->addWeek()]);

    $this->stripe->respond('delete', '#^/v1/subscriptions/#',
        Billing::stripeSubscription($renewing->stripe_id, $user->stripe_id, ['status' => 'canceled']));

    app(AccountDeletionService::class)->process(dueDeletionRequest($user));

    $user->refresh();

    expect($renewing->fresh()->ended())->toBeTrue()
        ->and($this->stripe->sent('delete', '#/v1/subscriptions/'.$renewing->stripe_id.'$#'))->toBeTrue()
        ->and($this->stripe->sent('delete', '#/v1/subscriptions/'.$alreadyCancelling->stripe_id.'$#'))->toBeFalse()
        ->and($user->name)->toBe('Deleted User')
        ->and($user->pm_last_four)->toBeNull()
        // Kept so invoices and refunds can still be found in Stripe.
        ->and($user->stripe_id)->not->toBeNull();
});

it('stops the deletion when Stripe cannot cancel, so the job retries', function () {
    $user = User::factory()->create(['name' => 'Still Paying']);
    Billing::subscribe($user);
    $request = dueDeletionRequest($user);

    expect(fn () => app(AccountDeletionService::class)->process($request))->toThrow(ApiErrorException::class);

    expect($user->fresh()->name)->toBe('Still Paying')
        ->and($request->fresh()->status)->toBe('processing');
});
