<?php

namespace App\Casts;

use App\Services\CurrencyService;
use App\Support\Money;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Casts an integer minor-units column (cents/paisa/halala) to a Money value
 * object. Usage:
 *
 *   protected function casts(): array
 *   {
 *       return ['price' => MoneyCast::class.':currency'];
 *   }
 *
 * where `currency` is the name of a sibling column on the same row holding
 * that record's own currency code — the currency a record was actually
 * charged/stored in, which must never change after the fact. Omit the
 * argument to always use the app's primary currency instead.
 */
class MoneyCast implements CastsAttributes
{
    public function __construct(private ?string $currencyColumn = null) {}

    public function get(Model $model, string $key, mixed $value, array $attributes): ?Money
    {
        if ($value === null) {
            return null;
        }

        $currencyCode = $this->currencyColumn !== null
            ? ($attributes[$this->currencyColumn] ?? app(CurrencyService::class)->primaryCode())
            : app(CurrencyService::class)->primaryCode();

        return new Money((int) $value, $currencyCode);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): int
    {
        return $value instanceof Money ? $value->minorUnits : (int) $value;
    }
}
