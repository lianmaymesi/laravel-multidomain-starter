<?php

use App\Models\User;
use Atrium\Core\Jobs\DeleteUserAccount;
use Atrium\Core\Models\AccountDeletionRequest;
use Atrium\Core\Services\AccountDeletionService;
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

it('stops the deletion when Stripe cannot cancel, and completes it on retry', function () {
    $user = User::factory()->create(['name' => 'Still Paying']);
    $subscription = Billing::subscribe($user);
    $request = dueDeletionRequest($user);

    expect(fn () => (new DeleteUserAccount($request))->handle(app(AccountDeletionService::class)))
        ->toThrow(ApiErrorException::class);

    // Nothing changed, and the request is pending again so a retry runs it.
    expect($user->fresh()->name)->toBe('Still Paying')
        ->and($request->fresh()->status)->toBe('pending');

    $this->stripe->respond('delete', '#^/v1/subscriptions/#',
        Billing::stripeSubscription($subscription->stripe_id, $user->stripe_id, ['status' => 'canceled']));

    (new DeleteUserAccount($request->fresh()))->handle(app(AccountDeletionService::class));

    expect($user->fresh()->name)->toBe('Deleted User')
        ->and($subscription->fresh()->ended())->toBeTrue()
        ->and($request->fresh()->status)->toBe('completed');
});
