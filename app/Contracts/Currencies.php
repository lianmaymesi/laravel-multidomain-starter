<?php

namespace App\Contracts;

/**
 * What core code (Money, MoneyCast) needs from the currency feature. Bound to
 * NullCurrencies by default; the Currency module rebinds it to the real,
 * database-backed service while enabled — so core works either way.
 */
interface Currencies
{
    public function primaryCode(): string;

    /** Format an integer minor-units amount (cents/paisa/halala) for display. */
    public function format(int $minorUnits, ?string $code = null): string;

    /** Display-only conversion between two currencies, in minor units. */
    public function convert(int $minorUnits, string $from, string $to): int;
}
