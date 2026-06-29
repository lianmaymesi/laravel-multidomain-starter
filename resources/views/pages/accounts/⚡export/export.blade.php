@php $title = 'Export Data'; @endphp

<div class="space-y-10">

    {{-- ═══════════════════════════════════════════════════════════════
    SECTION · Export your data
    ════════════════════════════════════════════════════════════════ --}}
    <section class="space-y-5">

        <div class="flex items-start justify-between gap-4">
            <div>
                <flux:heading size="lg" class="text-white!">Export your data</flux:heading>
                <flux:text class="text-white/40! text-sm!">Download a copy of your account data. Exports include profile
                    info and session history in CSV format, packaged as a ZIP file.</flux:text>
            </div>
            <flux:button wire:click="exportData"
                wire:confirm="We'll generate your data export and email you a download link. This may take a few minutes. Continue?"
                variant="primary" size="sm" class="rounded-2xl! shrink-0" icon="arrow-down-tray">
                <span wire:loading.remove wire:target="exportData">Request export</span>
                <span wire:loading wire:target="exportData">Queuing…</span>
            </flux:button>
        </div>

        @if (session('exportQueued'))
        <div class="flex items-center gap-3 rounded-2xl border border-blue-500/20 bg-blue-500/10 px-5 py-4">
            <flux:icon.arrow-path class="size-5 text-blue-400 shrink-0" />
            <div>
                <p class="text-sm font-medium text-blue-300">Export queued</p>
                <p class="text-xs text-blue-400/70 mt-0.5">You'll receive an email with your download link shortly. It
                    may take a few minutes.</p>
            </div>
        </div>
        @endif

        {{-- Export list --}}
        @if ($exports->isEmpty())
        <div
            class="flex flex-col items-center justify-center rounded-[1.75rem] border border-white/[0.07] bg-white/3 px-6 py-16 text-center">
            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/5 mb-4">
                <flux:icon.archive-box class="size-6 text-white/20" />
            </div>
            <p class="text-sm font-medium text-white/50">No exports yet</p>
            <p class="text-xs text-white/30 mt-1">Request an export above and we'll email you when it's ready.</p>
        </div>
        @else
        <div class="rounded-[1.75rem] border border-white/[0.07] bg-white/3 divide-y divide-white/5 overflow-hidden">

            {{-- List header --}}
            <div class="grid grid-cols-[1fr_auto_auto] gap-4 px-6 py-3">
                <p class="text-xs font-medium text-white/30 uppercase tracking-wider">Generated</p>
                <p class="text-xs font-medium text-white/30 uppercase tracking-wider">Expires</p>
                <p class="text-xs font-medium text-white/30 uppercase tracking-wider w-24 text-right">Action</p>
            </div>

            @foreach ($exports as $export)
            @php $expired = $export->isExpired(); @endphp
            <div class="grid grid-cols-[1fr_auto_auto] items-center gap-4 px-6 py-4
                        {{ $expired ? 'opacity-50' : '' }}">

                <div class="space-y-0.5 min-w-0">
                    <div class="flex items-center gap-2">
                        <flux:icon.document-arrow-down
                            class="size-4 {{ $expired ? 'text-white/20' : 'text-white/50' }} shrink-0" />
                        <p class="text-sm text-white/80 truncate">
                            {{ $export->created_at->format('M j, Y · g:i A') }}
                        </p>
                    </div>
                    <p class="text-xs text-white/30 pl-6">{{ $export->created_at->diffForHumans() }}</p>
                </div>

                <div class="shrink-0">
                    @if ($expired)
                    <span
                        class="rounded-full border border-white/[0.08] bg-white/5 px-2.5 py-1 text-[10px] font-medium text-white/25">
                        Expired
                    </span>
                    @else
                    <div class="text-right">
                        <span
                            class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2.5 py-1 text-[10px] font-medium text-emerald-400">
                            Available
                        </span>
                        <p class="text-[10px] text-white/25 mt-1">until {{ $export->expires_at->format('M j') }}</p>
                    </div>
                    @endif
                </div>

                <div class="w-24 flex justify-end">
                    @if (! $expired)
                    <flux:button :href="$export->downloadUrl()" size="xs" variant="ghost" class="rounded-xl!"
                        icon="arrow-down-tray">
                        Download
                    </flux:button>
                    @else
                    <span class="text-xs text-white/20 pr-1">—</span>
                    @endif
                </div>

            </div>
            @endforeach

        </div>
        @endif

        {{-- Info note --}}
        <div class="flex items-start gap-3 rounded-2xl border border-white/[0.05] bg-white/[0.02] px-5 py-4">
            <flux:icon.information-circle class="size-4 text-white/25 mt-0.5 shrink-0" />
            <p class="text-xs text-white/35 leading-relaxed">
                Export files are available for <span class="text-white/50">{{
                    \App\Models\AccountDataExport::EXPORT_TTL_DAYS }} days</span> after generation, then permanently
                deleted from our servers. Download and store them safely before they expire.
            </p>
        </div>

    </section>

</div>
