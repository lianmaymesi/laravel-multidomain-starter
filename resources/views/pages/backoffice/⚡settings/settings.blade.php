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

    @if ($this->fields()->isNotEmpty())
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

        <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
    </form>
    @endif

    {{-- Cards contributed by feature modules (Module::contribute 'backoffice.settings.cards'). --}}
    @foreach ($this->cards() as $card)
    @livewire($card['component'], [], key($card['component']))
    @endforeach

</div>
