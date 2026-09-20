@php $title = __('Translations'); @endphp

<div class="space-y-8">

    <div>
        <flux:heading size="xl">{{ __('Translations') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-500 dark:text-white/50">{{ __("Overrides for hardcoded strings and validation messages. Anything left blank falls back to the app's default text.") }}</flux:text>
    </div>

    @if (session('status'))
    <div class="border border-emerald-500/20 bg-emerald-500/6 px-4 py-3 text-sm text-emerald-400">
        {{ session('status') }}
    </div>
    @endif

    @php $stats = $this->stats(); @endphp
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <flux:card class="space-y-1">
            <flux:text class="text-xs text-zinc-500 dark:text-white/40">{{ __('Total strings') }}</flux:text>
            <flux:heading size="xl">{{ $stats['total'] }}</flux:heading>
        </flux:card>

        <flux:card class="space-y-1">
            <flux:text class="text-xs text-zinc-500 dark:text-white/40">{{ __('Missing translations') }}</flux:text>
            <flux:heading size="xl">{{ $stats['missing'] }}</flux:heading>
        </flux:card>

        <flux:card class="space-y-1">
            <flux:text class="text-xs text-zinc-500 dark:text-white/40">{{ __('Active languages') }}</flux:text>
            <flux:heading size="xl">{{ $stats['languages'] }}</flux:heading>
        </flux:card>

        <flux:card class="space-y-2">
            <flux:text class="text-xs text-zinc-500 dark:text-white/40">{{ __('Pending sync') }}</flux:text>
            <div class="flex items-center justify-between gap-3">
                <flux:heading size="xl">{{ $stats['pending'] }}</flux:heading>
                @if ($stats['pending'] > 0)
                <flux:button size="sm" wire:click="syncPending" wire:loading.attr="disabled">{{ __('Sync now') }}</flux:button>
                @else
                <flux:badge size="sm" color="emerald">{{ __('Up to date') }}</flux:badge>
                @endif
            </div>
        </flux:card>
    </div>

    @php $scopeCounts = $this->scopeCounts(); @endphp
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-zinc-200 dark:border-white/6">
        <div class="flex gap-1">
            @foreach ($this->allowedScopes() as $scopeOption)
            <a href="{{ route('backoffice.translations.index', $scopeOption === \App\Modules\Language\Models\LanguageLine::SCOPE_LANDING ? [] : ['scope' => $scopeOption]) }}" wire:navigate
                class="flex items-center gap-2 border-b-2 px-4 py-2 text-sm font-medium capitalize transition-colors
                    {{ $scope === $scopeOption ? 'border-blue-400 text-zinc-900 dark:text-white' : 'border-transparent text-zinc-500 dark:text-white/40 hover:text-zinc-700 dark:hover:text-white/70' }}">
                {{ $scopeOption }}
                <flux:badge size="sm" color="zinc">{{ $scopeCounts[$scopeOption] ?? 0 }}</flux:badge>
            </a>
            @endforeach
        </div>

        <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search by key or value…') }}" icon="magnifying-glass" class="mb-2 max-w-xs" />
    </div>

    {{-- Language switcher — a single page-wide toggle, not one field per
        language per line, so a translator only ever sees (and can only
        ever edit) the one language they're responsible for. --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2">
            <flux:text class="text-xs font-medium uppercase tracking-wider text-zinc-400 dark:text-white/30">{{ __('Editing language') }}</flux:text>
            @foreach ($this->languages() as $language)
            <flux:button size="sm" :variant="$editingLocale === $language->code ? 'primary' : 'ghost'" wire:click="setEditingLocale('{{ $language->code }}')">
                {{ $language->native_name }}
            </flux:button>
            @endforeach
        </div>

        @if ($this->canUseGoogleTranslate())
        <flux:button size="sm" icon="language" wire:click="translateWithGoogle" wire:loading.attr="disabled" wire:confirm="{{ __('Machine-translate every string still missing a :locale value? Existing translations are never overwritten.', ['locale' => strtoupper($editingLocale)]) }}">
            {{ __('Translate with Google') }}
        </flux:button>
        @endif
    </div>

    <div class="space-y-3">
        @forelse ($this->lines() as $line)
        <flux:card class="space-y-3" wire:key="line-{{ $line->id }}">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <flux:text class="font-mono text-xs wrap-break-word">{{ $line->key }}</flux:text>
                    <flux:badge size="sm" color="zinc">{{ $line->group === '*' ? 'text' : $line->group }}</flux:badge>
                    @foreach ($line->placeholders() as $token)
                    <flux:badge size="sm" color="amber">{{ $token }}</flux:badge>
                    @endforeach
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <flux:button size="sm" wire:click="save({{ $line->id }})">{{ __('Save') }}</flux:button>
                    <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $line->id }})" wire:confirm="{{ __('Remove this line?') }}" />
                </div>
            </div>

            <flux:field>
                <flux:label class="text-[10px] uppercase tracking-wider">{{ strtoupper($editingLocale) }}</flux:label>
                <flux:textarea wire:model="values.{{ $line->id }}.{{ $editingLocale }}" rows="auto" resize="none" />
                <flux:error name="values.{{ $line->id }}.{{ $editingLocale }}" />
            </flux:field>
        </flux:card>
        @empty
        <flux:card class="text-center text-zinc-500 dark:text-white/50">
            {{ __('No overrides in this scope yet.') }}
        </flux:card>
        @endforelse
    </div>

    <flux:card class="space-y-4">
        <flux:heading size="lg">{{ __('Add line') }}</flux:heading>
        <flux:text class="text-xs text-zinc-500 dark:text-white/40">{{ $newGroup === 'validation' ? __('A validation rule name, e.g. "required" or "custom.email.required".') : __('The exact string as it appears in the code — this is how __() looks it up.') }}</flux:text>

        <form wire:submit="addLine" class="space-y-4">
            <div class="flex flex-wrap items-start gap-4">
                <flux:field class="w-36 shrink-0">
                    <flux:label>{{ __('Group') }}</flux:label>
                    <flux:select wire:model.live="newGroup">
                        <flux:select.option value="*">{{ __('Text') }}</flux:select.option>
                        <flux:select.option value="validation">{{ __('Validation') }}</flux:select.option>
                    </flux:select>
                </flux:field>

                <flux:field class="min-w-64 flex-1">
                    <flux:label>{{ __('Key') }}</flux:label>
                    <flux:input wire:model="newKey" placeholder="{{ $newGroup === 'validation' ? __('required') : __('Welcome back, :name!') }}" />
                    <flux:error name="newKey" />
                </flux:field>
            </div>

            <flux:field>
                <flux:label class="text-[10px] uppercase tracking-wider">{{ strtoupper($editingLocale) }}</flux:label>
                <flux:textarea wire:model="newValues.{{ $editingLocale }}" rows="auto" resize="none" />
                <flux:error name="newValues.{{ $editingLocale }}" />
            </flux:field>

            <flux:button type="submit" variant="primary">{{ __('Add line') }}</flux:button>
        </form>
    </flux:card>

</div>
