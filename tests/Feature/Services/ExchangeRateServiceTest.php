<?php

use App\Models\Currency;
use App\Services\ExchangeRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

it('refreshes active currencies from frankfurter and leaves the primary at rate 1', function () {
    Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_digits' => 2, 'is_primary' => true, 'is_active' => true, 'exchange_rate' => 1]);
    Currency::create(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimal_digits' => 2, 'is_active' => true]);
    Currency::create(['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹', 'decimal_digits' => 2, 'is_active' => true]);
    Currency::create(['code' => 'GBP', 'name' => 'British Pound', 'symbol' => '£', 'decimal_digits' => 2, 'is_active' => false]);

    Http::fake([
        'api.frankfurter.app/*' => Http::response([
            'amount' => 1,
            'base' => 'USD',
            'date' => '2026-09-18',
            'rates' => ['EUR' => 0.92, 'INR' => 83.1],
        ]),
    ]);

    $updated = app(ExchangeRateService::class)->refresh();

    expect($updated)->toBe(2);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'from=USD') && str_contains($request->url(), 'to=EUR%2CINR');
    });

    expect((float) Currency::where('code', 'EUR')->value('exchange_rate'))->toBe(0.92)
        ->and((float) Currency::where('code', 'INR')->value('exchange_rate'))->toBe(83.1)
        ->and((float) Currency::where('code', 'USD')->value('exchange_rate'))->toBe(1.0)
        ->and(Currency::where('code', 'GBP')->value('exchange_rate'))->toBeNull();
});

it('does nothing when the API call fails', function () {
    Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_digits' => 2, 'is_primary' => true, 'is_active' => true, 'exchange_rate' => 1]);
    Currency::create(['code' => 'EUR', 'name' => 'Euro', 'symbol' => '€', 'decimal_digits' => 2, 'is_active' => true]);

    Http::fake(['api.frankfurter.app/*' => Http::response([], 500)]);

    expect(app(ExchangeRateService::class)->refresh())->toBe(0);
});
