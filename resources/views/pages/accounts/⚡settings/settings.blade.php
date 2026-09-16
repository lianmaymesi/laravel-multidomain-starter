@php $title = 'Settings'; @endphp

<div class="space-y-8">

    <div>
        <flux:heading size="xl">Settings</flux:heading>
        <flux:text class="mt-1 text-zinc-500 dark:text-white/50">Account-wide preferences.</flux:text>
    </div>

    @if ($this->isMultiLanguageEnabled())
    <flux:card class="space-y-4">
        <div>
            <flux:heading size="lg">Preferred language</flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-white/50">Applies across every portal on your account, overriding your browser's language on future visits.</flux:text>
        </div>

        <form wire:submit="save" class="flex items-end gap-3">
            <flux:field class="w-64">
                <flux:label>Language</flux:label>
                <flux:select wire:model="locale">
                    @foreach ($this->languages() as $language)
                    <flux:select.option value="{{ $language->code }}">{{ $language->native_name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="locale" />
            </flux:field>

            <flux:button type="submit" variant="primary">Save</flux:button>
        </form>
    </flux:card>
    @endif

</div>
