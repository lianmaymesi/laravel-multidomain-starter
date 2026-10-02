@php
    $title = __('System health');
    $report = $this->report();
    $overall = $report->status()->value;
    $tones = [
        'ok' => ['emerald', 'check-circle', __('Healthy'), 'text-emerald-500'],
        'warning' => ['amber', 'exclamation-triangle', __('Needs attention'), 'text-amber-500'],
        'failed' => ['red', 'x-circle', __('Failing'), 'text-red-500'],
    ];
@endphp

<div class="space-y-6">

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('System health') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-white/50">
                {{ __('Checked :when.', ['when' => $report->checkedAt->diffForHumans()]) }}
            </flux:text>
        </div>
        <flux:button type="button" icon="arrow-path" wire:click="runNow" wire:loading.attr="disabled">{{ __('Run checks now') }}</flux:button>
    </div>

    <flux:card class="flex items-center gap-4" data-overall="{{ $overall }}">
        <flux:icon :name="$tones[$overall][1]" class="size-8 {{ $tones[$overall][3] }}" />
        <div>
            <flux:heading size="lg">{{ $tones[$overall][2] }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-white/50">
                {{ trans_choice(':count check|:count checks', count($report->checks)) }}
                · {{ collect($report->checks)->filter(fn ($c) => $c['result']->status->value !== 'ok')->count() }} {{ __('need attention') }}
            </flux:text>
        </div>
    </flux:card>

    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($report->checks as $name => $check)
        @php $status = $check['result']->status->value; @endphp
        <flux:card class="space-y-2" wire:key="check-{{ $name }}" data-check="{{ $name }}" data-status="{{ $status }}">
            <div class="flex items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <flux:icon :name="$tones[$status][1]" class="size-5 {{ $tones[$status][3] }}" />
                    <flux:heading>{{ __($check['label']) }}</flux:heading>
                </div>
                <flux:badge size="sm" :color="$tones[$status][0]">{{ strtoupper($status) }}</flux:badge>
            </div>
            <flux:text class="text-sm">{{ $check['result']->message }}</flux:text>
            @if ($check['result']->meta !== [])
            <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-0.5 text-xs text-zinc-500 dark:text-white/40">
                @foreach ($check['result']->meta as $key => $value)
                <dt>{{ str($key)->headline() }}</dt>
                <dd class="truncate">{{ is_array($value) ? collect($value)->map(fn ($v, $k) => is_string($k) ? "{$k}: {$v}" : $v)->implode(', ') : (is_bool($value) ? ($value ? 'yes' : 'no') : $value) }}</dd>
                @endforeach
            </dl>
            @endif
            <flux:text class="text-[11px] text-zinc-400 dark:text-white/30">{{ $check['result']->durationMs }} ms</flux:text>
        </flux:card>
        @endforeach
    </div>

    <flux:card class="space-y-2 text-sm">
        <flux:heading>{{ __('Monitoring') }}</flux:heading>
        <flux:text>{{ __('Point your uptime monitor at:') }} <code>{{ route('health') }}</code> {{ __('— 200 while healthy or warning, 503 when a check fails.') }}</flux:text>
        @if ($this->tokenConfigured())
        <flux:text>{{ __('Send the X-Health-Token header (HEALTH_TOKEN) to get every check\'s detail.') }}</flux:text>
        @else
        <flux:text class="text-amber-600 dark:text-amber-400" data-no-token>{{ __('HEALTH_TOKEN is not set, so /health only ever shows the overall status. Set it to let your monitor see the details.') }}</flux:text>
        @endif
        <flux:text>{{ __('Liveness only (no checks):') }} <code>{{ url('/up') }}</code> · {{ __('CLI:') }} <code>php artisan health:check</code></flux:text>
    </flux:card>

</div>
