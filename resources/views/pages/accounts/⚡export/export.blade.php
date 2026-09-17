@php $title = __('Export Data'); @endphp

<div class="space-y-10">

    {{-- ═══════════════════════════════════════════════════════════════
    SECTION · Export your data
    ════════════════════════════════════════════════════════════════ --}}
    <section class="space-y-5">

        <div class="flex items-start justify-between gap-4">
            <div>
                <flux:heading size="lg" class="text-zinc-900! dark:text-white!">{{ __('Export your data') }}</flux:heading>
                <flux:text class="text-zinc-500! dark:text-white/40! text-sm!">{{ __('Download a copy of your account data. Each export is packaged as a ZIP with CSV files.') }}</flux:text>
            </div>
            <flux:button wire:click="exportData"
                wire:confirm="{{ __('We\'ll generate your data export and email you when it\'s ready. Continue?') }}"
                variant="primary" size="sm" class="shrink-0" icon="arrow-down-tray">
                <span wire:loading.remove wire:target="exportData">{{ __('New export') }}</span>
                <span wire:loading wire:target="exportData">{{ __('Queuing…') }}</span>
            </flux:button>
        </div>

        @if (session('exportQueued'))
        <div class="flex items-center gap-3 rounded-2xl border border-blue-500/20 bg-blue-50 dark:bg-blue-500/10 px-5 py-4">
            <flux:icon.arrow-path class="size-5 text-blue-600 dark:text-blue-400 shrink-0 animate-spin" />
            <div>
                <p class="text-sm font-medium text-blue-700 dark:text-blue-300">{{ __('Export queued') }}</p>
                <p class="text-xs text-blue-600 dark:text-blue-400/70 mt-0.5">{{ __("Generating your export. You'll receive an email with a download link once ready.") }}</p>
            </div>
        </div>
        @endif

        {{-- Export list --}}
        @if ($exports->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-[1.75rem] border border-zinc-200 dark:border-white/[0.07] bg-zinc-50 dark:bg-white/3 px-6 py-16 text-center">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-white/5 mb-4">
                <flux:icon.archive-box class="size-6 text-zinc-300 dark:text-white/20" />
            </div>
            <p class="text-sm font-medium text-zinc-500 dark:text-white/50">{{ __('No exports yet') }}</p>
            <p class="text-xs text-zinc-400 dark:text-white/30 mt-1">{{ __("Request an export above. It'll appear here once queued.") }}</p>
        </div>
        @else

        <div class="rounded-[1.75rem] border border-zinc-200 dark:border-white/[0.07] bg-zinc-50 dark:bg-white/3 divide-y divide-zinc-200 dark:divide-white/5 overflow-hidden">

            {{-- Table header --}}
            <div class="hidden sm:grid sm:grid-cols-[auto_1fr_1fr_1fr_auto] items-center gap-4 px-6 py-3">
                <div class="w-6"></div>
                <p class="text-xs font-medium text-zinc-400 dark:text-white/30 uppercase tracking-wider">{{ __('Exported') }}</p>
                <p class="text-xs font-medium text-zinc-400 dark:text-white/30 uppercase tracking-wider">{{ __('Expires') }}</p>
                <p class="text-xs font-medium text-zinc-400 dark:text-white/30 uppercase tracking-wider">{{ __('Last downloaded') }}</p>
                <p class="text-xs font-medium text-zinc-400 dark:text-white/30 uppercase tracking-wider w-28 text-end">{{ __('Action') }}</p>
            </div>

            @foreach ($exports as $export)
            @php
                $isProcessing = $export->isProcessing();
                $isReady      = $export->isReady();
                $isExpired    = $export->isExpired();
                $isActive     = $downloadExportId === $export->id;
            @endphp

            <div class="{{ $isExpired ? 'opacity-50' : '' }}">

                {{-- Main row --}}
                <div class="grid grid-cols-[auto_1fr_auto] sm:grid-cols-[auto_1fr_1fr_1fr_auto] items-center gap-4 px-6 py-4">

                    {{-- Status icon --}}
                    <div class="w-6 flex justify-center">
                        @if ($isProcessing)
                        <flux:icon.arrow-path class="size-4 text-blue-600 dark:text-blue-400 animate-spin" />
                        @elseif ($isReady)
                        <flux:icon.document-check class="size-4 text-emerald-400" />
                        @else
                        <flux:icon.document class="size-4 text-zinc-300 dark:text-white/20" />
                        @endif
                    </div>

                    {{-- Exported date --}}
                    <div class="min-w-0">
                        <p class="text-sm text-zinc-800 dark:text-white/80 truncate">{{ $export->created_at->format('M j, Y') }}</p>
                        <p class="text-xs text-zinc-400 dark:text-white/35">{{ $export->created_at->format('g:i A') }} · {{ $export->created_at->diffForHumans() }}</p>
                    </div>

                    {{-- Expires (hidden on mobile) --}}
                    <div class="hidden sm:block">
                        @if ($isProcessing)
                        <p class="text-xs text-zinc-400 dark:text-white/30">{{ __('Preparing…') }}</p>
                        @elseif ($isReady)
                        <p class="text-xs text-zinc-600 dark:text-white/60">{{ $export->expires_at->format('M j, Y') }}</p>
                        <p class="text-xs text-zinc-400 dark:text-white/30">{{ $export->expires_at->diffForHumans() }}</p>
                        @else
                        <span class="rounded-full border border-zinc-200 dark:border-white/[0.08] bg-zinc-100 dark:bg-white/5 px-2 py-0.5 text-[10px] font-medium text-zinc-400 dark:text-white/25">{{ __('Expired') }}</span>
                        @endif
                    </div>

                    {{-- Last downloaded (hidden on mobile) --}}
                    <div class="hidden sm:block">
                        @if ($export->downloaded_at)
                        <p class="text-xs text-zinc-600 dark:text-white/60">{{ $export->downloaded_at->format('M j, Y · g:i A') }}</p>
                        <p class="text-xs text-zinc-400 dark:text-white/30">{{ $export->download_count }}× total</p>
                        @else
                        <p class="text-xs text-zinc-400 dark:text-white/25">{{ __('Never downloaded') }}</p>
                        @endif
                    </div>

                    {{-- Action --}}
                    <div class="w-28 flex justify-end">
                        @if ($isReady && ! $isActive)
                        <flux:button wire:click="initiateDownload({{ $export->id }})" size="xs" variant="ghost"
                            icon="arrow-down-tray">
                            {{ __('Download') }}
                        </flux:button>
                        @elseif ($isActive)
                        <flux:button wire:click="cancelDownload" size="xs" variant="ghost"
                            class="text-zinc-500! dark:text-white/40!">
                            {{ __('Cancel') }}
                        </flux:button>
                        @elseif ($isProcessing)
                        <span class="text-xs text-blue-500 dark:text-blue-400/60 pe-1">{{ __('Processing…') }}</span>
                        @else
                        <span class="text-xs text-zinc-300 dark:text-white/20 pe-1">—</span>
                        @endif
                    </div>

                </div>

                {{-- Inline password confirm (expands under the row) --}}
                @if ($isActive)
                <div class="border-t border-zinc-200 dark:border-white/5 mx-6 mb-4 pt-4 space-y-3">
                    <p class="text-xs text-zinc-500 dark:text-white/50">{{ __('Enter your password to download. You can download up to 3 times per 24 hours.') }}</p>
                    <div class="flex items-start gap-3">
                        <div class="flex-1 space-y-1.5">
                            <flux:input
                                wire:model="downloadPassword"
                                type="password"
                                size="sm"
                                placeholder="{{ __('Your password') }}"
                                autocomplete="current-password"
                                viewable
                                wire:keydown.enter="confirmDownload"
                                wire:keydown.escape="cancelDownload"
                                x-init="$el.querySelector('input')?.focus()"
                            />
                            @error('downloadPassword')
                            <p class="text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                        <flux:button wire:click="confirmDownload" size="sm" variant="primary" class="shrink-0">
                            <span wire:loading.remove wire:target="confirmDownload">{{ __('Download') }}</span>
                            <span wire:loading wire:target="confirmDownload">{{ __('Verifying…') }}</span>
                        </flux:button>
                    </div>
                </div>
                @endif

            </div>
            @endforeach

        </div>
        @endif

        {{-- Info note --}}
        <div class="flex items-start gap-3 rounded-2xl border border-zinc-200 dark:border-white/[0.05] bg-zinc-50 dark:bg-white/[0.02] px-5 py-4">
            <flux:icon.information-circle class="size-4 text-zinc-400 dark:text-white/25 mt-0.5 shrink-0" />
            <p class="text-xs text-zinc-400 dark:text-white/35 leading-relaxed">
                {!! __('Export files are available for :days days after generation, then permanently deleted. Downloads are password-protected and limited to :max per 24 hours.', [
                    'days' => '<span class="text-zinc-500 dark:text-white/50">'.\App\Models\AccountDataExport::EXPORT_TTL_DAYS.'</span>',
                    'max' => '<span class="text-zinc-500 dark:text-white/50">'.\App\Models\AccountDataExport::MAX_DOWNLOADS_PER_DAY.'</span>',
                ]) !!}
            </p>
        </div>

    </section>

</div>
