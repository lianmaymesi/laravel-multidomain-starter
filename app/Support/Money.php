<?php

namespace App\Support;

use App\Contracts\Currencies;

/**
 * A read-only snapshot of an integer minor-units amount (cents/paisa/halala)
 * plus the currency it was stored in — deliberately immutable. Formatting or
 * converting a Money instance never rewrites the record it came from; once
 * an order is placed in a currency, that currency (and its stored amount)
 * never changes, no matter how exchange rates move afterward.
 */
final class Money
{
    public function __construct(
        public readonly int $minorUnits,
        public readonly string $currencyCode,
    ) {}

    public function format(): string
    {
        return app(Currencies::class)->format($this->minorUnits, $this->currencyCode);
    }

    /**
     * A display-only conversion to another currency — returns a new Money
     * instance, never mutates this one or anything persisted.
     */
    public function convertTo(string $currencyCode): self
    {
        return new self(
            app(Currencies::class)->convert($this->minorUnits, $this->currencyCode, $currencyCode),
            $currencyCode,
        );
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
