@php $title = 'Translations'; @endphp

<div class="space-y-8">

    <div>
        <flux:heading size="xl">Translations</flux:heading>
        <flux:text class="mt-1 text-zinc-500 dark:text-white/50">Overrides for hardcoded strings and validation messages. Anything left blank falls back to the app's default text.</flux:text>
    </div>

    @if (session('status'))
    <div class="border border-emerald-500/20 bg-emerald-500/6 px-4 py-3 text-sm text-emerald-400">
        {{ session('status') }}
    </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-zinc-200 dark:border-white/6">
        <div class="flex gap-1">
            @foreach ($this->allowedScopes() as $scopeOption)
            <a href="{{ route('backoffice.translations.index', $scopeOption === \App\Models\LanguageLine::SCOPE_LANDING ? [] : ['scope' => $scopeOption]) }}" wire:navigate
                class="border-b-2 px-4 py-2 text-sm font-medium capitalize transition-colors
                    {{ $scope === $scopeOption ? 'border-blue-400 text-zinc-900 dark:text-white' : 'border-transparent text-zinc-500 dark:text-white/40 hover:text-zinc-700 dark:hover:text-white/70' }}">
                {{ $scopeOption }}
            </a>
            @endforeach
        </div>

        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search by key or value…" icon="magnifying-glass" class="mb-2 max-w-xs" />
    </div>

    <div class="space-y-3">
        @forelse ($this->lines() as $line)
        <flux:card class="flex flex-wrap items-center gap-4" wire:key="line-{{ $line->id }}">
            <div class="flex w-56 shrink-0 items-center gap-2">
                <flux:text class="font-mono text-xs wrap-break-word">{{ $line->key }}</flux:text>
                <flux:badge size="sm" color="zinc">{{ $line->group === '*' ? 'text' : $line->group }}</flux:badge>
            </div>

            @foreach ($this->languages() as $language)
            <flux:field class="w-56 shrink-0">
                <flux:label class="text-[10px] uppercase tracking-wider">{{ $language->code }}</flux:label>
                <flux:input wire:model="values.{{ $line->id }}.{{ $language->code }}" size="sm" />
            </flux:field>
            @endforeach

            <div class="ms-auto flex shrink-0 items-center gap-2">
                <flux:button size="sm" wire:click="save({{ $line->id }})">Save</flux:button>
                <flux:button size="sm" variant="ghost" icon="trash" wire:click="delete({{ $line->id }})" wire:confirm="Remove this line?" />
            </div>
        </flux:card>
        @empty
        <flux:card class="text-center text-zinc-500 dark:text-white/50">
            No overrides in this scope yet.
        </flux:card>
        @endforelse
    </div>

    <flux:card class="space-y-4">
        <flux:heading size="lg">Add line</flux:heading>
        <flux:text class="text-xs text-zinc-500 dark:text-white/40">{{ $newGroup === 'validation' ? 'A validation rule name, e.g. "required" or "custom.email.required".' : 'The exact string as it appears in the code — this is how __() looks it up.' }}</flux:text>

        <form wire:submit="addLine" class="flex flex-wrap items-start gap-4">
            <flux:field class="w-36 shrink-0">
                <flux:label>Group</flux:label>
                <flux:select wire:model.live="newGroup">
                    <flux:select.option value="*">Text</flux:select.option>
                    <flux:select.option value="validation">Validation</flux:select.option>
                </flux:select>
            </flux:field>

            <flux:field class="w-72 shrink-0">
                <flux:label>Key</flux:label>
                <flux:input wire:model="newKey" placeholder="{{ $newGroup === 'validation' ? 'required' : 'Welcome back, :name!' }}" />
                <flux:error name="newKey" />
            </flux:field>

            @foreach ($this->languages() as $language)
            <flux:field class="w-56 shrink-0">
                <flux:label class="text-[10px] uppercase tracking-wider">{{ $language->code }}</flux:label>
                <flux:input wire:model="newValues.{{ $language->code }}" />
            </flux:field>
            @endforeach

            <flux:button type="submit" variant="primary" class="self-end">Add line</flux:button>
        </form>
    </flux:card>

</div>
