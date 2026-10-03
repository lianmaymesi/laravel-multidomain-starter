<?php

use App\Models\User;
use App\Modules\Billing\Health\BillingCheck;
use App\Support\Health\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\Billing\Billing;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->enableModules('billing');
    Billing::configure();
});

it('fails while Stripe keys or the webhook secret are missing', function () {
    config(['cashier.webhook.secret' => null]);

    $result = app(BillingCheck::class)->run();

    expect($result->status)->toBe(Status::Failed)
        ->and($result->meta['missing'])->toBe(['STRIPE_WEBHOOK_SECRET']);
});

it('fails when no plan has a price', function () {
    config(['billing.plans.pro.price' => null]);

    expect(app(BillingCheck::class)->run()->status)->toBe(Status::Failed);
});

it('warns about past-due subscriptions and is ok otherwise', function () {
    expect(app(BillingCheck::class)->run()->status)->toBe(Status::Ok);

    Billing::subscribe(User::factory()->create(), ['stripe_status' => 'past_due']);

    $result = app(BillingCheck::class)->run();

    expect($result->status)->toBe(Status::Warning)
        ->and($result->meta['past_due'])->toBe(1);
});
