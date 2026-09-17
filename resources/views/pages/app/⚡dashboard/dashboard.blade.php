@php $title = __('Dashboard'); @endphp

<div class="space-y-8">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-white/50">
                @if (auth()->check())
                {{ __("Welcome back, :name — here's what's happening today.", ['name' => auth()->user()->name]) }}
                @else
                {{ __("Welcome back — here's what's happening today.") }}
                @endif
            </flux:text>
        </div>
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($stats as $stat)
        <flux:card class="space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-500/15">
                    <flux:icon :icon="$stat['icon']" class="size-4.5 text-blue-600 dark:text-blue-400" />
                </div>
                <flux:badge :color="$stat['positive'] ? 'emerald' : 'red'" size="sm">{{ $stat['delta'] }}</flux:badge>
            </div>
            <div>
                <flux:heading size="lg">{{ $stat['value'] }}</flux:heading>
                <flux:text class="text-zinc-500 dark:text-white/50 text-sm">{{ $stat['label'] }}</flux:text>
            </div>
        </flux:card>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

        {{-- Weekly activity chart --}}
        <flux:card class="lg:col-span-2 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="lg">{{ __('Weekly Activity') }}</flux:heading>
                    <flux:text class="text-zinc-500 dark:text-white/50 text-sm">{{ __('Sessions recorded over the last 7 days') }}</flux:text>
                </div>
                <flux:badge color="blue" size="sm">+18.2%</flux:badge>
            </div>

            @php $max = max(array_column($weeklyActivity, 'value')); @endphp
            <div class="flex h-48 items-end gap-3">
                @foreach ($weeklyActivity as $day)
                <div class="flex flex-1 flex-col items-center gap-2">
                    <div class="flex h-40 w-full items-end overflow-hidden rounded-lg bg-zinc-100 dark:bg-white/5">
                        <div class="w-full rounded-lg bg-linear-to-t from-blue-600 to-blue-400"
                            style="height: {{ (int) round(($day['value'] / $max) * 100) }}%"></div>
                    </div>
                    <flux:text class="text-xs text-zinc-500 dark:text-white/40">{{ $day['label'] }}</flux:text>
                </div>
                @endforeach
            </div>
        </flux:card>

        {{-- Recent activity --}}
        <flux:card class="space-y-5">
            <flux:heading size="lg">{{ __('Recent Activity') }}</flux:heading>

            <div class="space-y-4">
                @foreach ($activity as $item)
                @php
                    $chip = match ($item['color']) {
                        'emerald' => ['bg-emerald-500/15', 'text-emerald-400'],
                        'amber' => ['bg-amber-500/15', 'text-amber-400'],
                        default => ['bg-blue-100 dark:bg-blue-500/15', 'text-blue-600 dark:text-blue-400'],
                    };
                @endphp
                <div class="flex items-start gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chip[0] }}">
                        <flux:icon :icon="$item['icon']" class="size-4 {{ $chip[1] }}" />
                    </div>
                    <div class="min-w-0">
                        <flux:text class="text-sm text-zinc-800 dark:text-white/80">{{ $item['title'] }}</flux:text>
                        <flux:text class="block text-xs text-zinc-500 dark:text-white/40">{{ $item['meta'] }}</flux:text>
                    </div>
                </div>
                @endforeach
            </div>
        </flux:card>

    </div>

</div>
