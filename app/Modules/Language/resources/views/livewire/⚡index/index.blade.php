@php $title = __('Languages'); @endphp

<div class="space-y-8">

    <div>
        <flux:heading size="xl">{{ __('Languages') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-500 dark:text-white/50">{{ __('Manage the languages visitors can switch between. Locale switching and translation UI stay hidden across every portal until at least two are active.') }}</flux:text>
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

    <div class="space-y-3">
        @foreach ($this->languages() as $index => $language)
        <flux:card class="flex items-center justify-between gap-4" wire:key="language-{{ $language->id }}">
            <div class="flex items-center gap-3">
                <div class="flex flex-col">
                    <flux:button variant="ghost" size="sm" square icon="chevron-up" wire:click="move({{ $language->id }}, -1)" :disabled="$index === 0" />
                    <flux:button variant="ghost" size="sm" square icon="chevron-down" wire:click="move({{ $language->id }}, 1)" :disabled="$index === $this->languages()->count() - 1" />
                </div>

                <div>
                    <div class="flex items-center gap-2">
                        <flux:heading size="lg">{{ $language->name }}</flux:heading>
                        <flux:text class="text-zinc-500 dark:text-white/50">{{ $language->native_name }}</flux:text>
                        @if ($language->is_primary)
                        <flux:badge size="sm" color="blue">{{ __('Primary') }}</flux:badge>
                        @endif
                        <flux:badge size="sm" color="zinc">{{ strtoupper($language->code) }}</flux:badge>
                        <flux:badge size="sm" color="zinc">{{ strtoupper($language->direction) }}</flux:badge>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @can('languages.edit')
                @unless ($language->is_primary)
                <flux:button variant="ghost" size="sm" wire:click="makePrimary({{ $language->id }})">{{ __('Make primary') }}</flux:button>
                @endunless
                <flux:switch :checked="(bool) $language->is_active" wire:click="toggleActive({{ $language->id }})" />
                @endcan

                @can('languages.delete')
                @unless ($language->is_primary)
                <flux:button variant="ghost" size="sm" icon="trash" wire:click="delete({{ $language->id }})" wire:confirm="{{ __('Remove this language?') }}" />
                @endunless
                @endcan
            </div>
        </flux:card>
        @endforeach

        @if ($this->languages()->isEmpty())
        <flux:card class="text-center text-zinc-500 dark:text-white/50">
            {!! __('No languages configured yet — the app is running on :locale from config.', ['locale' => '<code>'.config('app.locale').'</code>']) !!}
        </flux:card>
        @endif
    </div>

    @can('languages.create')
    <flux:card class="space-y-4">
        <flux:heading size="lg">{{ __('Add language') }}</flux:heading>

        <form wire:submit="add" class="grid grid-cols-1 gap-4 sm:grid-cols-4 sm:items-end">
            <flux:field>
                <flux:label>{{ __('Code') }}</flux:label>
                <flux:input wire:model="code" placeholder="{{ __('ar') }}" maxlength="2" />
                <flux:error name="code" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Name') }}</flux:label>
                <flux:input wire:model="name" placeholder="{{ __('Arabic') }}" />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Native name') }}</flux:label>
                <flux:input wire:model="native_name" placeholder="العربية" />
                <flux:error name="native_name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Direction') }}</flux:label>
                <flux:select wire:model="direction">
                    <flux:select.option value="ltr">{{ __('LTR') }}</flux:select.option>
                    <flux:select.option value="rtl">{{ __('RTL') }}</flux:select.option>
                </flux:select>
            </flux:field>

            <div class="sm:col-span-4">
                <flux:button type="submit" variant="primary">{{ __('Add language') }}</flux:button>
            </div>
        </form>
    </flux:card>
    @endcan

</div>
