@php $title = __('Feature Flags'); @endphp

<div class="space-y-8">

    <div>
        <flux:heading size="xl">{{ __('Feature Flags') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-500 dark:text-white/50">{{ __('Turn individual features on or off per portal or per user. Changes apply on the next page load — no deploy needed. To remove a whole feature from the app, use Modules instead.') }}</flux:text>
    </div>

    @unless ($this->migrated())
    <div class="border border-amber-500/20 bg-amber-500/6 px-4 py-3 text-sm text-amber-400">
        {!! __('Feature flags cannot be read or changed yet — the database is missing a table. Run :command, then reload this page.', ['command' => '<code>php artisan migrate</code>']) !!}
    </div>
    @endunless

    @if ($status)
    <div class="border border-emerald-500/20 bg-emerald-500/6 px-4 py-3 text-sm text-emerald-400">
        {{ $status }}
    </div>
    @endif

    <div class="space-y-4">
        @forelse ($this->flags() as $flag)
        <flux:card class="space-y-4" wire:key="flag-{{ $flag['name'] }}">
            <div class="flex flex-wrap items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:heading size="lg">{{ __($flag['label']) }}</flux:heading>
                        <flux:badge size="sm" :color="$flag['scope'] === 'portal' ? 'blue' : 'violet'">
                            {{ $flag['scope'] === 'portal' ? __('Per portal') : __('Per user') }}
                        </flux:badge>
                    </div>
                    <flux:text class="mt-1 font-mono text-xs text-zinc-400 dark:text-white/30">{{ $flag['name'] }}</flux:text>
                    @if ($flag['description'] !== '')
                    <flux:text class="mt-1 text-zinc-500 dark:text-white/50">{{ __($flag['description']) }}</flux:text>
                    @endif
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <flux:button type="button" size="sm" :disabled="! $this->migrated()" wire:click="setEverywhere('{{ $flag['name'] }}', true)">{{ __('All on') }}</flux:button>
                    <flux:button type="button" size="sm" :disabled="! $this->migrated()" wire:click="setEverywhere('{{ $flag['name'] }}', false)">{{ __('All off') }}</flux:button>
                    <flux:button type="button" variant="ghost" size="sm" :disabled="! $this->migrated()"
                        wire:click="resetFlag('{{ $flag['name'] }}')"
                        wire:confirm="{{ __('Reset :flag? Every stored value is forgotten and decided again by the flag\'s default rule.', ['flag' => __($flag['label'])]) }}">{{ __('Reset') }}</flux:button>
                </div>
            </div>

            @if ($flag['scope'] === 'portal' && $flag['portals'] !== [])
            <div class="flex flex-wrap gap-x-6 gap-y-3 border-t border-zinc-200 pt-4 dark:border-white/10">
                @foreach ($flag['portals'] as $portal => $active)
                <div class="flex items-center gap-2" wire:key="flag-{{ $flag['name'] }}-{{ $portal }}">
                    <flux:switch
                        :checked="$active"
                        wire:click="setPortal('{{ $flag['name'] }}', '{{ $portal }}', {{ $active ? 'false' : 'true' }})"
                        aria-label="{{ __(':flag in the :portal portal', ['flag' => __($flag['label']), 'portal' => $portal]) }}"
                    />
                    <flux:text class="text-sm">{{ str($portal)->headline() }}</flux:text>
                </div>
                @endforeach
            </div>
            @endif

            @if ($flag['scope'] === 'user' && $flag['users'] !== null)
            <flux:text class="border-t border-zinc-200 pt-4 text-sm text-zinc-500 dark:border-white/10 dark:text-white/50">
                {{ __('On for :active of :total users decided so far. Users seen for the first time are decided by the flag\'s default rule.', $flag['users']) }}
            </flux:text>
            @endif
        </flux:card>
        @empty
        <flux:card>
            <flux:text class="text-zinc-500 dark:text-white/50">{!! __('No feature flags yet. Create one with :command.', ['command' => '<code>php artisan make:flag NewThing</code>']) !!}</flux:text>
        </flux:card>
        @endforelse
    </div>

</div>
