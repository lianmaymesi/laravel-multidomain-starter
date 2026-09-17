@php $title = __('Settings'); @endphp

<div class="space-y-8">

    <div>
        <flux:heading size="xl">{{ __('Settings') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-500 dark:text-white/50">{{ __('Account-wide preferences.') }}</flux:text>
    </div>

    @if (session('status'))
    <div class="border border-emerald-500/20 bg-emerald-500/6 px-4 py-3 text-sm text-emerald-400">
        {{ session('status') }}
    </div>
    @endif

    <form wire:submit="save" class="space-y-8">

        @if ($this->isMultiLanguageEnabled())
        <flux:card class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Preferred language') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-500 dark:text-white/50">{{ __("Applies across every portal on your account, overriding your browser's language on future visits.") }}</flux:text>
            </div>

            <flux:field class="w-64">
                <flux:label>{{ __('Language') }}</flux:label>
                <flux:select wire:model="locale">
                    @foreach ($this->languages() as $language)
                    <flux:select.option value="{{ $language->code }}">{{ $language->native_name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="locale" />
            </flux:field>
        </flux:card>
        @endif

        <flux:card class="space-y-4">
            <div>
                <flux:heading size="lg">{{ __('Timezone') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-500 dark:text-white/50">{{ __('Dates and times across your account are shown in this timezone.') }}</flux:text>
            </div>

            <flux:field class="w-96">
                <flux:label>{{ __('Timezone') }}</flux:label>
                <flux:select wire:model="timezone">
                    @foreach ($this->timezones() as $tz)
                    <flux:select.option value="{{ $tz }}">{{ $tz }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="timezone" />
            </flux:field>
        </flux:card>

        <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
    </form>

</div>
