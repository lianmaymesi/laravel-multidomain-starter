<?php

namespace App\Support;

use App\Contracts\Currencies;

/**
 * Fallback while the Currency module is off: no rates, no per-currency
 * formatting rules. Amounts are shown as "CODE 12.34" assuming two decimals,
 * and conversions return the amount unchanged.
 */
class NullCurrencies implements Currencies
{
    public function primaryCode(): string
    {
        return 'USD';
    }

    public function format(int $minorUnits, ?string $code = null): string
    {
        return ($code ?? $this->primaryCode()).' '.number_format($minorUnits / 100, 2);
    }

    public function convert(int $minorUnits, string $from, string $to): int
    {
        return $minorUnits;
    }
}
