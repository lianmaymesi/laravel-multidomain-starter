<?php

namespace App\Services;

use App\Models\Currency;
use Illuminate\Support\Collection;

class CurrencyService
{
    /**
     * @return Collection<int, Currency>
     */
    public function active(): Collection
    {
        return Currency::active();
    }

    /**
     * @return array<int, string>
     */
    public function activeCodes(): array
    {
        return $this->active()->pluck('code')->all();
    }

    public function isMultiCurrencyEnabled(): bool
    {
        return $this->active()->count() >= 2;
    }

    public function primary(): ?Currency
    {
        return Currency::primary();
    }

    public function primaryCode(): string
    {
        return $this->primary()?->code ?? 'USD';
    }

    public function find(string $code): ?Currency
    {
        return $this->active()->firstWhere('code', $code);
    }

    /**
     * Formats an integer minor-units amount (cents/paisa/halala) for
     * display — never used to reconvert or rewrite a value already
     * persisted against a specific currency (e.g. a placed order); it's
     * read-only presentation.
     */
    public function format(int $minorUnits, ?string $code = null): string
    {
        $currency = $this->find($code ?? $this->primaryCode());

        if ($currency === null) {
            return (string) $minorUnits;
        }

        $amount = $minorUnits / (10 ** $currency->decimal_digits);
        $formatted = number_format($amount, $currency->decimal_digits);

        return $currency->symbol !== null
            ? $currency->symbol.$formatted
            : $currency->code.' '.$formatted;
    }

    /**
     * Converts a minor-units amount between two active currencies using
     * their exchange rate relative to the primary currency — a display/quote
     * conversion only, never used to mutate an amount already stored on a
     * record (an order keeps whatever currency it was placed in, forever).
     */
    public function convert(int $minorUnits, string $from, string $to): int
    {
        if ($from === $to) {
            return $minorUnits;
        }

        $fromCurrency = $this->find($from);
        $toCurrency = $this->find($to);

        if ($fromCurrency?->exchange_rate === null || $toCurrency?->exchange_rate === null) {
            return $minorUnits;
        }

        $fromAmount = $minorUnits / (10 ** $fromCurrency->decimal_digits);
        $primaryAmount = $fromAmount / (float) $fromCurrency->exchange_rate;
        $toAmount = $primaryAmount * (float) $toCurrency->exchange_rate;

        return (int) round($toAmount * (10 ** $toCurrency->decimal_digits));
    }
}
