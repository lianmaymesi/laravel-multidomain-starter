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

    {{-- Example feature flag (app/Features/WhatsNewCard.php) — off until switched
        on for the app portal from Backoffice → Feature Flags. --}}
    @flag(\App\Features\WhatsNewCard::class)
    <flux:card class="flex items-start gap-4" data-flag="whats-new-card">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-500/15">
            <flux:icon.sparkles class="size-4.5 text-blue-600 dark:text-blue-400" />
        </div>
        <div>
            <flux:heading size="lg">{{ __("What's new") }}</flux:heading>
            <flux:text class="text-zinc-500 dark:text-white/50 text-sm">{{ __('This card is behind a feature flag. Switch it off again from Backoffice → Feature Flags — no deploy needed.') }}</flux:text>
        </div>
    </flux:card>
    @endflag

    {{-- Advanced demo flag (app/Features/HelloWorldAdvanced.php) — the value is
        an A/B variant, not just on/off. "All on" stores plain true → classic. --}}
    @flag(\App\Features\HelloWorldAdvanced::class)
    @php
        $variant = \App\Support\Features\Flags::value(\App\Features\HelloWorldAdvanced::class);
        $variant = in_array($variant, \App\Features\HelloWorldAdvanced::VARIANTS, true) ? $variant : 'classic';
    @endphp
    <flux:card class="flex items-start gap-4" data-flag="hello-world-advanced" data-variant="{{ $variant }}">
        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-violet-100 dark:bg-violet-500/15">
            @if ($variant === 'rocket')
            <flux:icon.rocket-launch class="size-4.5 text-violet-600 dark:text-violet-400" />
            @elseif ($variant === 'wave')
            <flux:icon.hand-raised class="size-4.5 text-violet-600 dark:text-violet-400" />
            @else
            <flux:icon.globe-alt class="size-4.5 text-violet-600 dark:text-violet-400" />
            @endif
        </div>
        <div>
            <flux:heading size="lg">
                @switch($variant)
                @case('rocket') {{ __('Hello, World — now 10x faster!') }} @break
                @case('wave') {{ __('Hey :name, welcome aboard!', ['name' => auth()->user()?->name ?? __('there')]) }} @break
                @default {{ __('Hello, World') }}
                @endswitch
            </flux:heading>
            <flux:text class="text-zinc-500 dark:text-white/50 text-sm">{{ __('Feature flag demo — you are seeing variant ":variant". Manage it from Backoffice → Feature Flags.', ['variant' => $variant]) }}</flux:text>
        </div>
    </flux:card>
    @endflag

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

            <flux:chart :value="$weeklyActivity" class="aspect-3/1" data-chart="weekly-activity">
                <flux:chart.svg>
                    <flux:chart.line field="value" class="text-blue-500 dark:text-blue-400" />
                    <flux:chart.area field="value" class="text-blue-200/50 dark:text-blue-400/10" />

                    <flux:chart.axis axis="x" field="label">
                        <flux:chart.axis.line />
                        <flux:chart.axis.tick />
                    </flux:chart.axis>

                    <flux:chart.axis axis="y">
                        <flux:chart.axis.grid />
                        <flux:chart.axis.tick />
                    </flux:chart.axis>

                    <flux:chart.cursor />
                </flux:chart.svg>

                <flux:chart.tooltip>
                    <flux:chart.tooltip.heading field="label" />
                    <flux:chart.tooltip.value field="value" :label="__('Sessions')" />
                </flux:chart.tooltip>
            </flux:chart>
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
