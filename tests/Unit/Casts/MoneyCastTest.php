<?php

use App\Casts\MoneyCast;
use App\Models\Currency;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('casts an integer column to a Money object using a sibling currency column', function () {
    Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_digits' => 2, 'is_primary' => true, 'is_active' => true, 'exchange_rate' => 1]);
    Currency::create(['code' => 'INR', 'name' => 'Indian Rupee', 'symbol' => '₹', 'decimal_digits' => 2, 'is_active' => true]);

    $cast = new MoneyCast('currency');
    $model = new Currency;

    $money = $cast->get($model, 'price', 3000, ['currency' => 'INR']);

    expect($money)->toBeInstanceOf(Money::class)
        ->and($money->minorUnits)->toBe(3000)
        ->and($money->currencyCode)->toBe('INR')
        ->and($money->format())->toBe('₹30.00');
});

it('falls back to the primary currency when no currency column is configured', function () {
    Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_digits' => 2, 'is_primary' => true, 'is_active' => true, 'exchange_rate' => 1]);

    $cast = new MoneyCast;
    $model = new Currency;

    $money = $cast->get($model, 'price', 3000, []);

    expect($money->currencyCode)->toBe('USD');
});

it('returns null for a null value', function () {
    $cast = new MoneyCast;

    expect($cast->get(new Currency, 'price', null, []))->toBeNull();
});

it('casts a Money instance back to its integer minor units on set', function () {
    $cast = new MoneyCast('currency');

    expect($cast->set(new Currency, 'price', new Money(3000, 'INR'), []))->toBe(3000)
        ->and($cast->set(new Currency, 'price', 3000, []))->toBe(3000);
});
