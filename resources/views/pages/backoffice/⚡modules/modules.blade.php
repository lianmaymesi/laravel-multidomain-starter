@php $title = __('Modules'); @endphp

<div class="space-y-8">

    <div>
        <flux:heading size="xl">{{ __('Modules') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-500 dark:text-white/50">{{ __('Switch optional features on or off. A disabled module disappears everywhere — its pages, menu entries and background work — but its data is kept, so turning it back on restores it as it was.') }}</flux:text>
    </div>

    @if (session('status'))
    <div class="border border-emerald-500/20 bg-emerald-500/6 px-4 py-3 text-sm text-emerald-400">
        {{ session('status') }}
    </div>
    @endif

    <div class="space-y-4">
        @foreach ($this->modules() as $module)
        <flux:card class="flex flex-wrap items-center justify-between gap-4" wire:key="module-{{ $module['key'] }}">
            <div class="flex min-w-0 items-start gap-4">
                <div class="mt-0.5 flex size-9 shrink-0 items-center justify-center border border-zinc-200 dark:border-white/10 {{ $module['enabled'] ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-400 dark:text-white/30' }}">
                    <flux:icon :name="$module['icon']" class="size-4" />
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:heading size="lg">{{ __($module['label']) }}</flux:heading>
                        <flux:badge size="sm" :color="$module['enabled'] ? 'emerald' : 'zinc'">
                            {{ $module['enabled'] ? __('Enabled') : __('Disabled') }}
                        </flux:badge>
                        @if ($module['overridden'])
                        <flux:badge size="sm" color="amber">
                            {{ $module['default'] ? __('Default: on') : __('Default: off') }}
                        </flux:badge>
                        @endif
                    </div>
                    @if ($module['description'] !== '')
                    <flux:text class="mt-1 text-zinc-500 dark:text-white/50">{{ __($module['description']) }}</flux:text>
                    @endif
                </div>
            </div>

            <div class="flex items-center gap-3">
                @if ($module['overridden'])
                <flux:button type="button" variant="ghost" size="sm" wire:click="resetToDefault('{{ $module['key'] }}')">{{ __('Reset to default') }}</flux:button>
                @endif

                <flux:switch
                    :checked="$module['enabled']"
                    wire:click="toggle('{{ $module['key'] }}')"
                    @if ($module['enabled']) wire:confirm="{{ __('Disable :module? Its pages and menu entries will disappear until it is enabled again. Data is kept.', ['module' => __($module['label'])]) }}" @endif
                />
            </div>
        </flux:card>
        @endforeach
    </div>

</div>
