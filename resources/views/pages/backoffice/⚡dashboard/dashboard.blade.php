@php $title = 'Dashboard'; @endphp

<div class="mx-auto max-w-7xl w-full px-4 py-10 sm:px-6 lg:px-8 space-y-8">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center gap-2">
                <flux:heading size="xl">Backoffice</flux:heading>
                <flux:badge color="zinc" size="sm">Staff</flux:badge>
            </div>
            <flux:text class="mt-1 text-white/50">Operational overview{{ auth()->check() ? ' for ' . auth()->user()->name : '' }}.</flux:text>
        </div>
        @auth
        <flux:avatar size="lg" name="{{ auth()->user()->name }}" />
        @endauth
    </div>

    {{-- Stat cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ($stats as $stat)
        <flux:card class="space-y-3">
            <div class="flex items-center justify-between">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-500/15">
                    <flux:icon :icon="$stat['icon']" class="size-4.5 text-blue-400" />
                </div>
                <flux:badge :color="$stat['positive'] ? 'emerald' : 'amber'" size="sm">{{ $stat['delta'] }}</flux:badge>
            </div>
            <div>
                <flux:heading size="lg">{{ $stat['value'] }}</flux:heading>
                <flux:text class="text-white/50 text-sm">{{ $stat['label'] }}</flux:text>
            </div>
        </flux:card>
        @endforeach
    </div>

    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">

        {{-- Ticket volume chart --}}
        <flux:card class="lg:col-span-2 space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="lg">Ticket Volume</flux:heading>
                    <flux:text class="text-white/50 text-sm">Support tickets opened over the last 7 days</flux:text>
                </div>
                <flux:badge color="amber" size="sm">67 open</flux:badge>
            </div>

            @php $max = max(array_column($ticketVolume, 'value')); @endphp
            <div class="flex h-48 items-end gap-3">
                @foreach ($ticketVolume as $day)
                <div class="flex flex-1 flex-col items-center gap-2">
                    <div class="flex h-40 w-full items-end overflow-hidden rounded-lg bg-white/5">
                        <div class="w-full rounded-lg bg-linear-to-t from-blue-600 to-blue-400"
                            style="height: {{ (int) round(($day['value'] / $max) * 100) }}%"></div>
                    </div>
                    <flux:text class="text-xs text-white/40">{{ $day['label'] }}</flux:text>
                </div>
                @endforeach
            </div>
        </flux:card>

        {{-- Audit log --}}
        <flux:card class="space-y-5">
            <flux:heading size="lg">Audit Log</flux:heading>

            <div class="space-y-4">
                @foreach ($auditLog as $item)
                @php
                    $chip = match ($item['color']) {
                        'emerald' => ['bg-emerald-500/15', 'text-emerald-400'],
                        'amber' => ['bg-amber-500/15', 'text-amber-400'],
                        default => ['bg-blue-500/15', 'text-blue-400'],
                    };
                @endphp
                <div class="flex items-start gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $chip[0] }}">
                        <flux:icon :icon="$item['icon']" class="size-4 {{ $chip[1] }}" />
                    </div>
                    <div class="min-w-0">
                        <flux:text class="text-sm text-white/80">{{ $item['title'] }}</flux:text>
                        <flux:text class="block text-xs text-white/40">{{ $item['meta'] }}</flux:text>
                    </div>
                </div>
                @endforeach
            </div>
        </flux:card>

    </div>

</div>
