<?php

use App\Models\AppSetting;
use App\Models\Currency;
use App\Services\ExchangeRateService;
use App\Services\SettingsRegistry;
use App\Support\Settings\SettingField;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts.backoffice')] class extends Component
{
    /**
     * Keyed by SettingField::$key. A secret field's entry is always blank
     * on load — the decrypted value is never round-tripped into the page;
     * a non-empty value here on save means "replace the stored key," not
     * "here's what's currently stored."
     *
     * @var array<string, mixed>
     */
    public array $values = [];

    /**
     * The pending set of active currency codes — a tag picker, not a long
     * checklist. Nothing here touches the database until the page's one
     * global Save button is clicked.
     *
     * @var array<int, string>
     */
    public array $activeCurrencyCodes = [];

    public string $addCurrencyCode = '';

    public string $primaryCurrency = '';

    public function mount(): void
    {
        abort_unless($this->fields()->isNotEmpty() || $this->canEditCurrencies(), 403);

        foreach ($this->fields() as $field) {
            $this->values[$field->key] = $field->type === SettingField::TYPE_SECRET ? '' : AppSetting::value($field);
        }

        $this->activeCurrencyCodes = Currency::where('is_active', true)->orderBy('order')->pluck('code')->all();
        $this->primaryCurrency = Currency::primary()?->code ?? '';
    }

    /**
     * @return \Illuminate\Support\Collection<int, SettingField>
     */
    public function fields()
    {
        return app(SettingsRegistry::class)->all()->filter(fn (SettingField $field) => Gate::allows($field->permission))->values();
    }

    public function isConfigured(string $key): bool
    {
        return AppSetting::hasEncrypted($key);
    }

    /**
     * One button saves everything on the page — settings fields and the
     * currency tag picker alike.
     */
    public function save(): void
    {
        $fields = $this->fields();
        $canEditCurrencies = $this->canEditCurrencies();
        abort_unless($fields->isNotEmpty() || $canEditCurrencies, 403);

        $rules = [];
        foreach ($fields as $field) {
            if ($field->rules !== []) {
                $rules['values.'.$field->key] = $field->rules;
            }
        }

        $this->validate($rules);

        if ($canEditCurrencies && $this->activeCurrencyCodes !== [] && ! in_array($this->primaryCurrency, $this->activeCurrencyCodes, true)) {
            $this->addError('primaryCurrency', __('Choose a primary currency.'));

            return;
        }

        foreach ($fields as $field) {
            if ($field->type === SettingField::TYPE_SECRET && ($this->values[$field->key] ?? '') === '') {
                continue;
            }

            AppSetting::saveField($field, $this->values[$field->key] ?? null);

            if ($field->type === SettingField::TYPE_SECRET) {
                $this->values[$field->key] = '';
            }
        }

        if ($canEditCurrencies) {
            $this->persistCurrencies();
        }

        session()->flash('status', __('Saved.'));
    }

    public function removeSecret(string $key): void
    {
        $field = app(SettingsRegistry::class)->find($key);
        abort_unless($field !== null && $field->type === SettingField::TYPE_SECRET && Gate::allows($field->permission), 403);

        AppSetting::setEncrypted($key, null);

        session()->flash('status', __('Saved.'));
    }

    public function canEditCurrencies(): bool
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
        if ($code === $this->primaryCurrency) {
            return;
        }

        $this->activeCurrencyCodes = array_values(array_diff($this->activeCurrencyCodes, [$code]));
    }

    private function persistCurrencies(): void
    {
        foreach (Currency::all() as $currency) {
            $isPrimary = $currency->code === $this->primaryCurrency;
            $isActive = $isPrimary || in_array($currency->code, $this->activeCurrencyCodes, true);

            if ($currency->is_active !== $isActive || $currency->is_primary !== $isPrimary) {
                $currency->update(['is_active' => $isActive, 'is_primary' => $isPrimary]);
            }
        }
    }

    public function refreshRates(ExchangeRateService $exchangeRates): void
    {
        abort_unless($this->canEditCurrencies(), 403);

        $updated = $exchangeRates->refresh();

        session()->flash('status', $updated > 0
            ? __('Refreshed :count exchange rate(s).', ['count' => $updated])
            : __('No active currencies to refresh.'));
    }
};
