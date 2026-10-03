<?php

use App\Modules\Currency\Models\Currency;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('formats money using the currency symbol and decimal digits', function () {
    Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_digits' => 2, 'is_primary' => true, 'is_active' => true, 'exchange_rate' => 1]);

    expect((new Money(3000, 'USD'))->format())->toBe('$30.00');
});

it('formats zero-decimal currencies without a decimal point', function () {
    Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_digits' => 2, 'is_primary' => true, 'is_active' => true, 'exchange_rate' => 1]);
    Currency::create(['code' => 'JPY', 'name' => 'Japanese Yen', 'symbol' => '¥', 'decimal_digits' => 0, 'is_active' => true]);

    expect((new Money(3000, 'JPY'))->format())->toBe('¥3,000');
});

it('formats three-decimal currencies correctly', function () {
    Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_digits' => 2, 'is_primary' => true, 'is_active' => true, 'exchange_rate' => 1]);
    Currency::create(['code' => 'BHD', 'name' => 'Bahraini Dinar', 'symbol' => '.د.ب', 'decimal_digits' => 3, 'is_active' => true]);

    expect((new Money(3000, 'BHD'))->format())->toBe('.د.ب3.000');
});

it('falls back to the currency code when no symbol is set', function () {
    Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => null, 'decimal_digits' => 2, 'is_primary' => true, 'is_active' => true, 'exchange_rate' => 1]);

    expect((new Money(3000, 'USD'))->format())->toBe('USD 30.00');
});

it('stringifies to its formatted value', function () {
    Currency::create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$', 'decimal_digits' => 2, 'is_primary' => true, 'is_active' => true, 'exchange_rate' => 1]);

    expect((string) new Money(3000, 'USD'))->toBe('$30.00');
});
