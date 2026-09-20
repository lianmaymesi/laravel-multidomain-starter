<?php

use App\Modules\Currency\Models\Currency;
use App\Modules\Currency\Services\ExchangeRateService;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

/**
 * The Currencies card on the backoffice Settings page — contributed through
 * the 'backoffice.settings.cards' extension point, with its own Save button.
 */
new class extends Component
{
    /**
     * The pending set of active currency codes — a tag picker, not a long
     * checklist. Nothing here touches the database until Save is clicked.
     *
     * @var array<int, string>
     */
    public array $activeCurrencyCodes = [];

    public string $addCurrencyCode = '';

    public string $primaryCurrency = '';

    public string $status = '';

    public function mount(): void
    {
        abort_unless($this->canEdit(), 403);

        $this->activeCurrencyCodes = Currency::where('is_active', true)->orderBy('order')->pluck('code')->all();
        $this->primaryCurrency = Currency::primary()?->code ?? '';
    }

    public function canEdit(): bool
    {
        return Gate::allows('settings.edit');
    }

    /**
     * @return \Illuminate\Support\Collection<int, Currency>
     */
    public function activeCurrencies()
    {
        return Currency::whereIn('code', $this->activeCurrencyCodes)->orderBy('name')->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, Currency>
     */
    public function availableCurrenciesToAdd()
    {
        return Currency::whereNotIn('code', $this->activeCurrencyCodes)->orderBy('name')->get();
    }

    public function addCurrency(): void
    {
        abort_unless($this->canEdit(), 403);

        if ($this->addCurrencyCode !== '' && ! in_array($this->addCurrencyCode, $this->activeCurrencyCodes, true)) {
            $this->activeCurrencyCodes[] = $this->addCurrencyCode;

            if ($this->primaryCurrency === '') {
                $this->primaryCurrency = $this->addCurrencyCode;
            }
        }

        $this->addCurrencyCode = '';
    }

    public function removeCurrency(string $code): void
    {
        abort_unless($this->canEdit(), 403);

        if ($code === $this->primaryCurrency) {
            return;
        }

        $this->activeCurrencyCodes = array_values(array_diff($this->activeCurrencyCodes, [$code]));
    }

    public function save(): void
    {
        abort_unless($this->canEdit(), 403);

        if ($this->activeCurrencyCodes !== [] && ! in_array($this->primaryCurrency, $this->activeCurrencyCodes, true)) {
            $this->addError('primaryCurrency', __('Choose a primary currency.'));

            return;
        }

        foreach (Currency::all() as $currency) {
            $isPrimary = $currency->code === $this->primaryCurrency;
            $isActive = $isPrimary || in_array($currency->code, $this->activeCurrencyCodes, true);

            if ($currency->is_active !== $isActive || $currency->is_primary !== $isPrimary) {
                $currency->update(['is_active' => $isActive, 'is_primary' => $isPrimary]);
            }
        }

        $this->status = __('Saved.');
    }

    public function refreshRates(ExchangeRateService $exchangeRates): void
    {
        abort_unless($this->canEdit(), 403);

        $updated = $exchangeRates->refresh();

        $this->status = $updated > 0
            ? __('Refreshed :count exchange rate(s).', ['count' => $updated])
            : __('No active currencies to refresh.');
    }
};
