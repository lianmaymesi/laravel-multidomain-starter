<?php

namespace App\Services;

use App\Models\Currency;
use Illuminate\Support\Facades\Http;

class ExchangeRateService
{
    /**
     * Refreshes every active (non-primary) currency's rate relative to the
     * primary currency, via Frankfurter.app (free, ECB-based, no API key).
     * Only ever updates the `currencies` table — never touches money
     * already stored against a specific currency on another model.
     *
     * @return int number of currencies updated
     */
    public function refresh(): int
    {
        $primary = Currency::primary();

        if ($primary === null) {
            return 0;
        }

        $targets = Currency::active()
            ->reject(fn (Currency $currency) => $currency->code === $primary->code)
            ->pluck('code');

        if ($targets->isEmpty()) {
            return 0;
        }

        $response = Http::get('https://api.frankfurter.app/latest', [
            'from' => $primary->code,
            'to' => $targets->implode(','),
        ]);

        if (! $response->successful()) {
            return 0;
        }

        $rates = $response->json('rates', []);
        $now = now();
        $updated = 0;

        foreach ($rates as $code => $rate) {
            $currency = Currency::active()->firstWhere('code', $code);

            if ($currency === null) {
                continue;
            }

            $currency->update(['exchange_rate' => $rate, 'rate_synced_at' => $now]);
            $updated++;
        }

        $primary->update(['exchange_rate' => 1, 'rate_synced_at' => $now]);

        return $updated;
    }
}
