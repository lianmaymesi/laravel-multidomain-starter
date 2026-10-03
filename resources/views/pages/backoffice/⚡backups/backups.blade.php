@php
    $title = __('Backups');
    $settings = $this->settings();
    $destinations = $this->destinations();
@endphp

<div class="space-y-6">

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Backups') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-white/50">
                {{ __('Database dump plus uploaded files, one zip per backup. Each one holds all of the app\'s data, so handle downloads with care.') }}
            </flux:text>
        </div>
        <form wire:submit="backUpNow" class="flex items-center gap-2">
            <flux:select wire:model="scope" size="sm" class="w-40" aria-label="{{ __('What to back up') }}">
                <flux:select.option value="full">{{ __('Database + files') }}</flux:select.option>
                <flux:select.option value="db">{{ __('Database only') }}</flux:select.option>
                <flux:select.option value="files">{{ __('Files only') }}</flux:select.option>
            </flux:select>
            <flux:button type="submit" icon="arrow-down-tray" variant="primary" size="sm" wire:loading.attr="disabled">{{ __('Back up now') }}</flux:button>
        </form>
    </div>

    @if (session('status'))
    <div class="border border-emerald-500/20 bg-emerald-500/6 px-4 py-3 text-sm text-emerald-400" data-status>
        {{ session('status') }}
    </div>
    @endif

    @unless ($settings['enabled'])
    <div class="border border-amber-500/20 bg-amber-500/6 px-4 py-3 text-sm text-amber-500" data-disabled>
        {!! __('Scheduled backups are off. Set :env in .env to back up every night; "Back up now" works either way.', ['env' => '<code>BACKUP_ENABLED=true</code>']) !!}
    </div>
    @endunless

    <flux:card class="grid gap-x-6 gap-y-2 text-sm sm:grid-cols-2 lg:grid-cols-3">
        <div>
            <flux:text class="text-xs text-zinc-500 dark:text-white/40">{{ __('Schedule') }}</flux:text>
            <flux:text>
                @if ($settings['enabled'])
                {{ __('Daily at :run (cleanup :clean, check :monitor)', ['run' => $settings['schedule']['run_at'] ?? '', 'clean' => $settings['schedule']['clean_at'] ?? '', 'monitor' => $settings['schedule']['monitor_at'] ?? '']) }}
                @else
                {{ __('Off') }}
                @endif
            </flux:text>
        </div>
        <div>
            <flux:text class="text-xs text-zinc-500 dark:text-white/40">{{ __('Destinations') }}</flux:text>
            <flux:text>{{ implode(', ', $settings['disks']) }}</flux:text>
        </div>
        <div>
            <flux:text class="text-xs text-zinc-500 dark:text-white/40">{{ __('Failure alerts') }}</flux:text>
            <flux:text>{{ $settings['recipients'] > 0 ? trans_choice(':count recipient|:count recipients', $settings['recipients']) : __('None — set BACKUP_NOTIFY_MAIL') }}</flux:text>
        </div>
        <div>
            <flux:text class="text-xs text-zinc-500 dark:text-white/40">{{ __('Encryption') }}</flux:text>
            <flux:text>{{ $settings['encrypted'] ? __('AES-256 (BACKUP_ARCHIVE_PASSWORD)') : __('None') }}</flux:text>
        </div>
        <div>
            <flux:text class="text-xs text-zinc-500 dark:text-white/40">{{ __('Queue') }}</flux:text>
            <flux:text>{{ $settings['queue'] }}</flux:text>
        </div>
    </flux:card>

    @if (count($settings['disks']) < 2)
    <flux:text class="text-sm text-amber-600 dark:text-amber-400" data-single-disk>
        {{ __('Only one destination. If it is on this server, the backups are lost along with the server — add an off-site disk to BACKUP_DISKS (e.g. backups,s3).') }}
    </flux:text>
    @endif

    @foreach ($destinations as $destination)
    <flux:card class="space-y-4" wire:key="disk-{{ $destination['disk'] }}" data-disk="{{ $destination['disk'] }}">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <flux:icon.server-stack class="size-5 text-zinc-400 dark:text-white/30" />
                <flux:heading size="lg">{{ $destination['disk'] }}</flux:heading>
                @if (! $destination['reachable'])
                <flux:badge size="sm" color="red">{{ __('Unreachable') }}</flux:badge>
                @elseif ($destination['healthy'])
                <flux:badge size="sm" color="emerald">{{ __('Healthy') }}</flux:badge>
                @else
                <flux:badge size="sm" color="amber">{{ __('Needs attention') }}</flux:badge>
                @endif
            </div>
            <flux:text class="text-sm text-zinc-500 dark:text-white/50">
                {{ trans_choice(':count backup|:count backups', count($destination['backups'])) }} · {{ $this->formatBytes($destination['used_bytes']) }}
            </flux:text>
        </div>

        @foreach ($destination['problems'] as $problem)
        <flux:text class="text-sm text-amber-600 dark:text-amber-400">{{ $problem }}</flux:text>
        @endforeach

        @if ($destination['backups'] !== [])
        <flux:table>
            <flux:table.columns>
                <flux:table.column>{{ __('Taken') }}</flux:table.column>
                <flux:table.column>{{ __('File') }}</flux:table.column>
                <flux:table.column>{{ __('Size') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @foreach ($destination['backups'] as $backup)
                <flux:table.row wire:key="backup-{{ $destination['disk'] }}-{{ $backup['path'] }}" data-backup="{{ $backup['path'] }}">
                    <flux:table.cell>
                        {{ $backup['date']->toDayDateTimeString() }}
                        <span class="text-xs text-zinc-400 dark:text-white/30">· {{ $backup['date']->diffForHumans() }}</span>
                    </flux:table.cell>
                    <flux:table.cell class="font-mono text-xs">{{ basename($backup['path']) }}</flux:table.cell>
                    <flux:table.cell>{{ $this->formatBytes($backup['size_bytes']) }}</flux:table.cell>
                    <flux:table.cell class="text-end">
                        <div class="flex justify-end gap-1">
                            <flux:button size="sm" variant="ghost" icon="arrow-down-tray"
                                href="{{ route('backoffice.backups.download', ['disk' => $destination['disk'], 'path' => $backup['path']]) }}"
                                aria-label="{{ __('Download') }}" />
                            <flux:button size="sm" variant="ghost" icon="trash" type="button"
                                wire:click="delete({{ Js::from($destination['disk']) }}, {{ Js::from($backup['path']) }})"
                                wire:confirm="{{ __('Delete this backup permanently? This cannot be undone.') }}"
                                aria-label="{{ __('Delete') }}" />
                        </div>
                    </flux:table.cell>
                </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
        @elseif ($destination['reachable'])
        <flux:text class="text-sm text-zinc-500 dark:text-white/50">{{ __('No backups yet.') }}</flux:text>
        @endif
    </flux:card>
    @endforeach

    <flux:card class="space-y-2 text-sm">
        <flux:heading>{{ __('Restoring') }}</flux:heading>
        <flux:text>{{ __('Download a backup, unzip it (with BACKUP_ARCHIVE_PASSWORD if set), load db-dumps/*.sql into an empty database and copy private/ and public/ back into storage/app. The full procedure is in the README under "Backups → Restoring".') }}</flux:text>
        <flux:text>{{ __('CLI:') }} <code>php artisan backup:run</code> · <code>php artisan backup:list</code></flux:text>
    </flux:card>

</div>
