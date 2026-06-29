@php $title = 'Export Data'; @endphp

<div class="space-y-10">

    {{-- ═══════════════════════════════════════════════════════════════
    SECTION · Export your data
    ════════════════════════════════════════════════════════════════ --}}
    <section class="space-y-5">

        <div class="flex items-start justify-between gap-4">
            <div>
                <flux:heading size="lg" class="text-white!">Export your data</flux:heading>
                <flux:text class="text-white/40! text-sm!">Download a copy of your account data. Each export is packaged as a ZIP with CSV files.</flux:text>
            </div>
            <flux:button wire:click="exportData"
                wire:confirm="We'll generate your data export and email you when it's ready. Continue?"
                variant="primary" size="sm" class="rounded-2xl! shrink-0" icon="arrow-down-tray">
                <span wire:loading.remove wire:target="exportData">New export</span>
                <span wire:loading wire:target="exportData">Queuing…</span>
            </flux:button>
        </div>

        @if (session('exportQueued'))
        <div class="flex items-center gap-3 rounded-2xl border border-blue-500/20 bg-blue-500/10 px-5 py-4">
            <flux:icon.arrow-path class="size-5 text-blue-400 shrink-0 animate-spin" />
            <div>
                <p class="text-sm font-medium text-blue-300">Export queued</p>
                <p class="text-xs text-blue-400/70 mt-0.5">Generating your export. You'll receive an email with a download link once ready.</p>
            </div>
        </div>
        @endif

        {{-- Export list --}}
        @if ($exports->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-[1.75rem] border border-white/[0.07] bg-white/3 px-6 py-16 text-center">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/5 mb-4">
                <flux:icon.archive-box class="size-6 text-white/20" />
            </div>
            <p class="text-sm font-medium text-white/50">No exports yet</p>
            <p class="text-xs text-white/30 mt-1">Request an export above. It'll appear here once queued.</p>
        </div>
        @else

        <div class="rounded-[1.75rem] border border-white/[0.07] bg-white/3 divide-y divide-white/5 overflow-hidden">

            {{-- Table header --}}
            <div class="hidden sm:grid sm:grid-cols-[auto_1fr_1fr_1fr_auto] items-center gap-4 px-6 py-3">
                <div class="w-6"></div>
                <p class="text-xs font-medium text-white/30 uppercase tracking-wider">Exported</p>
                <p class="text-xs font-medium text-white/30 uppercase tracking-wider">Expires</p>
                <p class="text-xs font-medium text-white/30 uppercase tracking-wider">Last downloaded</p>
                <p class="text-xs font-medium text-white/30 uppercase tracking-wider w-28 text-right">Action</p>
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
                        <flux:icon.arrow-path class="size-4 text-blue-400 animate-spin" />
                        @elseif ($isReady)
                        <flux:icon.document-check class="size-4 text-emerald-400" />
                        @else
                        <flux:icon.document class="size-4 text-white/20" />
                        @endif
                    </div>

                    {{-- Exported date --}}
                    <div class="min-w-0">
                        <p class="text-sm text-white/80 truncate">{{ $export->created_at->format('M j, Y') }}</p>
                        <p class="text-xs text-white/35">{{ $export->created_at->format('g:i A') }} · {{ $export->created_at->diffForHumans() }}</p>
                    </div>

                    {{-- Expires (hidden on mobile) --}}
                    <div class="hidden sm:block">
                        @if ($isProcessing)
                        <p class="text-xs text-white/30">Preparing…</p>
                        @elseif ($isReady)
                        <p class="text-xs text-white/60">{{ $export->expires_at->format('M j, Y') }}</p>
                        <p class="text-xs text-white/30">{{ $export->expires_at->diffForHumans() }}</p>
                        @else
                        <span class="rounded-full border border-white/[0.08] bg-white/5 px-2 py-0.5 text-[10px] font-medium text-white/25">Expired</span>
                        @endif
                    </div>

                    {{-- Last downloaded (hidden on mobile) --}}
                    <div class="hidden sm:block">
                        @if ($export->downloaded_at)
                        <p class="text-xs text-white/60">{{ $export->downloaded_at->format('M j, Y · g:i A') }}</p>
                        <p class="text-xs text-white/30">{{ $export->download_count }}× total</p>
                        @else
                        <p class="text-xs text-white/25">Never downloaded</p>
                        @endif
                    </div>

                    {{-- Action --}}
                    <div class="w-28 flex justify-end">
                        @if ($isReady && ! $isActive)
                        <flux:button wire:click="initiateDownload({{ $export->id }})" size="xs" variant="ghost"
                            class="rounded-xl!" icon="arrow-down-tray">
                            Download
                        </flux:button>
                        @elseif ($isActive)
                        <flux:button wire:click="cancelDownload" size="xs" variant="ghost"
                            class="rounded-xl! text-white/40!">
                            Cancel
                        </flux:button>
                        @elseif ($isProcessing)
                        <span class="text-xs text-blue-400/60 pr-1">Processing…</span>
                        @else
                        <span class="text-xs text-white/20 pr-1">—</span>
                        @endif
                    </div>

                </div>

                {{-- Inline password confirm (expands under the row) --}}
                @if ($isActive)
                <div class="border-t border-white/5 mx-6 mb-4 pt-4 space-y-3">
                    <p class="text-xs text-white/50">Enter your password to download. You can download up to 3 times per 24 hours.</p>
                    <div class="flex items-start gap-3">
                        <div class="flex-1 space-y-1.5">
                            <flux:input
                                wire:model="downloadPassword"
                                type="password"
                                size="sm"
                                placeholder="Your password"
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
                        <flux:button wire:click="confirmDownload" size="sm" variant="primary" class="rounded-2xl! shrink-0">
                            <span wire:loading.remove wire:target="confirmDownload">Download</span>
                            <span wire:loading wire:target="confirmDownload">Verifying…</span>
                        </flux:button>
                    </div>
                </div>
                @endif

            </div>
            @endforeach

        </div>
        @endif

        {{-- Info note --}}
        <div class="flex items-start gap-3 rounded-2xl border border-white/[0.05] bg-white/[0.02] px-5 py-4">
            <flux:icon.information-circle class="size-4 text-white/25 mt-0.5 shrink-0" />
            <p class="text-xs text-white/35 leading-relaxed">
                Export files are available for <span class="text-white/50">{{ \App\Models\AccountDataExport::EXPORT_TTL_DAYS }} days</span> after generation, then permanently deleted. Downloads are password-protected and limited to <span class="text-white/50">{{ \App\Models\AccountDataExport::MAX_DOWNLOADS_PER_DAY }} per 24 hours</span>.
            </p>
        </div>

    </section>

</div>
