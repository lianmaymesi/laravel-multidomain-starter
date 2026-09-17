@php $title = __('Settings'); @endphp

<div class="space-y-8">

    <div>
        <flux:heading size="xl">{{ __('Settings') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-500 dark:text-white/50">{{ __('System-wide behavior.') }}</flux:text>
    </div>

    @if (session('status'))
    <div class="border border-emerald-500/20 bg-emerald-500/6 px-4 py-3 text-sm text-emerald-400">
        {{ session('status') }}
    </div>
    @endif

    @if (session('error'))
    <div class="border border-red-500/20 bg-red-500/6 px-4 py-3 text-sm text-red-400">
        {{ session('error') }}
    </div>
    @endif

    <form wire:submit="save" class="space-y-8">
        @foreach ($this->fields() as $field)
        <flux:card class="space-y-4" wire:key="field-{{ $field->key }}">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <flux:heading size="lg">{{ $field->label }}</flux:heading>
                    @if ($field->description)
                    <flux:text class="mt-1 text-zinc-500 dark:text-white/50">{{ $field->description }}</flux:text>
                    @endif
                </div>
                @if ($field->type === \App\Support\Settings\SettingField::TYPE_SECRET && $this->isConfigured($field->key))
                <flux:badge size="sm" color="emerald">{{ __('Configured') }}</flux:badge>
                @endif
            </div>

            @if ($field->type === \App\Support\Settings\SettingField::TYPE_RADIO)
            <flux:radio.group wire:model="values.{{ $field->key }}" variant="cards" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach ($field->options ?? [] as $value => $option)
                <flux:radio value="{{ $value }}" label="{{ $option['label'] }}" description="{{ $option['description'] }}" />
                @endforeach
            </flux:radio.group>
            <flux:error name="values.{{ $field->key }}" />
            @else
            <flux:field class="w-96">
                @switch($field->type)
                    @case(\App\Support\Settings\SettingField::TYPE_SELECT)
                        <flux:select wire:model="values.{{ $field->key }}">
                            @foreach ($field->options ?? [] as $value => $label)
                            <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        @break

                    @case(\App\Support\Settings\SettingField::TYPE_SECRET)
                        <flux:input wire:model="values.{{ $field->key }}" type="password" viewable
                            placeholder="{{ $this->isConfigured($field->key) ? __('Leave blank to keep the current key') : __('Paste your API key') }}" />
                        @break

                    @case(\App\Support\Settings\SettingField::TYPE_TEXTAREA)
                    @case(\App\Support\Settings\SettingField::TYPE_JSON)
                        <flux:textarea wire:model="values.{{ $field->key }}" rows="auto" resize="none" />
                        @break

                    @case(\App\Support\Settings\SettingField::TYPE_BOOLEAN)
                        <flux:switch wire:model="values.{{ $field->key }}" />
                        @break

                    @default
                        <flux:input wire:model="values.{{ $field->key }}" />
                @endswitch
                <flux:error name="values.{{ $field->key }}" />
            </flux:field>
            @endif

            @if ($field->type === \App\Support\Settings\SettingField::TYPE_SECRET)
                @if ($this->isConfigured($field->key))
                <flux:button type="button" variant="ghost" size="sm" wire:click="removeSecret('{{ $field->key }}')" wire:confirm="{{ __('Remove this key?') }}">
                    {{ __('Remove key') }}
                </flux:button>
                @endif

                @if ($field->helpUrl)
                <flux:text class="text-xs text-zinc-400 dark:text-white/30">
                    {!! __('Need a key? Get one from :link.', [
                        'link' => '<a href="'.$field->helpUrl.'" target="_blank" rel="noopener" class="underline">'.($field->helpText ?? $field->helpUrl).'</a>',
                    ]) !!}
                </flux:text>
                @endif
            @endif
        </flux:card>
        @endforeach

        @if ($this->canEditCurrencies())
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

            @if (count($this->activeCurrencyCodes) > 1)
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
        </flux:card>
        @endif

        <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
    </form>

</div>
