<?php

namespace App\Modules\Billing\Services;

use Illuminate\Support\Collection;

/**
 * The plan catalogue from config('billing.plans'), limited to plans that have
 * a Stripe price id — so a half-configured plan never shows up for sale.
 */
class Plans
{
    /** @return Collection<string, array{key: string, name: string, description: string, price: string, amount: string, features: array<int, string>}> */
    public function all(): Collection
    {
        return collect(config('billing.plans', []))
            ->filter(fn (array $plan) => filled($plan['price'] ?? null))
            ->map(fn (array $plan, string $key) => [
                'key' => $key,
                'name' => (string) ($plan['name'] ?? str($key)->headline()),
                'description' => (string) ($plan['description'] ?? ''),
                'price' => (string) $plan['price'],
                'amount' => (string) ($plan['amount'] ?? ''),
                'features' => array_values((array) ($plan['features'] ?? [])),
            ]);
    }

    /** @return array{key: string, name: string, description: string, price: string, amount: string, features: array<int, string>}|null */
    public function find(string $key): ?array
    {
        return $this->all()->get($key);
    }

    /** Plan name for a Stripe price id, or the raw id for a price not in config. */
    public function nameForPrice(?string $price): string
    {
        if ($price === null) {
            return '—';
        }

        return $this->all()->firstWhere('price', $price)['name'] ?? $price;
    }

    /** Stripe keys set and at least one plan for sale. */
    public function ready(): bool
    {
        return filled(config('cashier.key')) && filled(config('cashier.secret')) && $this->all()->isNotEmpty();
    }
}
