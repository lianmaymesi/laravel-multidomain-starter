<flux:card class="space-y-4">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="lg">{{ __('Currencies') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-white/50">
                {!! __('Which currencies are available and which one is primary — see the read-only :link page for rates and formatting per currency.', [
                    'link' => '<a href="'.route('backoffice.currencies.index').'" class="underline">'.__('Currencies').'</a>',
                ]) !!}
            </flux:text>
        </div>
        <flux:button type="button" size="sm" wire:click="refreshRates" wire:loading.attr="disabled">{{ __('Refresh rates') }}</flux:button>
    </div>

    @if ($status !== '')
    <div class="border border-emerald-500/20 bg-emerald-500/6 px-4 py-3 text-sm text-emerald-400">
        {{ $status }}
    </div>
    @endif

    <div class="flex flex-wrap gap-2">
        @forelse ($this->activeCurrencies() as $currency)
        <flux:badge size="lg" :color="$currency->code === $primaryCurrency ? 'blue' : 'zinc'" wire:key="tag-{{ $currency->code }}">
            {{ $currency->code }}
            @if ($currency->code === $primaryCurrency)
            <span class="ms-1 text-[10px] uppercase tracking-wider opacity-70">{{ __('Primary') }}</span>
            @else
            <button type="button" wire:click="removeCurrency('{{ $currency->code }}')" class="ms-1.5 opacity-60 hover:opacity-100" aria-label="{{ __('Remove :code', ['code' => $currency->code]) }}">&times;</button>
            @endif
        </flux:badge>
        @empty
        <flux:text class="text-sm text-zinc-400 dark:text-white/30">{{ __('No currencies added yet.') }}</flux:text>
        @endforelse
    </div>

    <div class="flex flex-wrap items-end gap-3">
        <flux:field class="w-64">
            <flux:label>{{ __('Add currency') }}</flux:label>
            <flux:select wire:model="addCurrencyCode">
                <flux:select.option value="">{{ __('Select a currency…') }}</flux:select.option>
                @foreach ($this->availableCurrenciesToAdd() as $currency)
                <flux:select.option value="{{ $currency->code }}">{{ $currency->name }} ({{ $currency->code }})</flux:select.option>
                @endforeach
            </flux:select>
        </flux:field>
        <flux:button type="button" size="sm" wire:click="addCurrency">{{ __('Add') }}</flux:button>
    </div>

    @if (count($activeCurrencyCodes) > 1)
    <flux:field class="w-64">
        <flux:label>{{ __('Primary currency') }}</flux:label>
        <flux:select wire:model="primaryCurrency">
            @foreach ($this->activeCurrencies() as $currency)
            <flux:select.option value="{{ $currency->code }}">{{ $currency->name }} ({{ $currency->code }})</flux:select.option>
            @endforeach
        </flux:select>
        <flux:error name="primaryCurrency" />
    </flux:field>
    @endif

    <flux:button type="button" variant="primary" wire:click="save">{{ __('Save currencies') }}</flux:button>
</flux:card>
