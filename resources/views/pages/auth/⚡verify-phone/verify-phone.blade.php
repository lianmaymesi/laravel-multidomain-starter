@php $title = 'Verify Phone'; @endphp

<div class="min-h-screen bg-zinc-950 flex items-center justify-center px-4 py-8">

    {{-- Background gradients --}}
    <div class="fixed inset-0 pointer-events-none">
        <div
            class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.18),_transparent_35%),radial-gradient(circle_at_20%_80%,_rgba(16,185,129,0.12),_transparent_28%)]">
        </div>
    </div>

    <div class="relative mx-auto w-full max-w-md space-y-6" x-data="{
            countdown: {{ $resendCooldown }},
            timer: null,
            start() {
                clearInterval(this.timer);
                if (this.countdown <= 0) return;
                this.timer = setInterval(() => {
                    this.countdown--;
                    if (this.countdown <= 0) clearInterval(this.timer);
                }, 1000);
            }
        }" x-init="start()">

        {{-- Header --}}
        <div class="inline-flex items-center gap-3 text-white">
            <span
                class="flex h-14 w-14 items-center justify-center rounded-2xl border border-white/10 bg-white/5 backdrop-blur-sm">
                <img src="{{ Vite::asset('resources/assets/images/logo.svg') }}" alt="{{ config('app.name') }}"
                    class="h-10 w-auto" />
            </span>
            <div>
                <flux:heading size="xl" class="!text-3xl font-semibold !tracking-tight text-white sm:!text-4xl">
                    Verify your phone
                </flux:heading>
                <flux:text class="hidden md:block text-sm leading-7 text-white/65">
                    Enter the 6-digit code we sent you
                </flux:text>
            </div>
        </div>

        {{-- Main Card --}}
        <div
            class="rounded-[1.75rem] border border-white/10 bg-white/5 p-6 shadow-2xl shadow-black/20 backdrop-blur-md space-y-5">

            {{-- Flash status --}}
            @if (session('status'))
            <div
                class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-400 text-center">
                {{ session('status') }}
            </div>
            @endif

            {{-- Phone row: display or edit form --}}
            @if ($editingPhone)
            <form wire:submit="updatePhone" class="space-y-3">
                <p class="text-sm text-white/50 text-center">Enter your updated phone number</p>

                <div class="flex gap-2">
                    <div class="w-24">
                        <flux:input wire:model="newCountryCode" placeholder="+91" class="text-center" />
                    </div>
                    <div class="flex-1">
                        <flux:input wire:model="newPhone" mask="99999-99999" placeholder="98765-43210" />
                    </div>
                </div>
                <flux:error name="newCountryCode" />
                <flux:error name="newPhone" />

                <div class="flex gap-2 pt-1">
                    <flux:button type="button" wire:click="cancelEdit" class="flex-1">Cancel</flux:button>
                    <flux:button type="submit" variant="primary" class="flex-1 rounded-3xl!">
                        <span wire:loading.remove wire:target="updatePhone">Update & Resend</span>
                        <span wire:loading wire:target="updatePhone">Saving…</span>
                    </flux:button>
                </div>
            </form>
            @else
            {{-- Phone display + edit button --}}
            <div class="flex items-center justify-between rounded-xl border border-white/10 bg-white/[0.04] px-4 py-3">
                <div>
                    <p class="text-xs text-white/35 mb-0.5">Code sent to</p>
                    <p class="text-sm font-medium text-white/85 tracking-wide">
                        {{ auth()->user()?->country_code }}
                        <span class="text-white/40">••••••</span>{{ substr(auth()->user()?->phone ?? '', -3) }}
                    </p>
                </div>

                @if ($editAttemptsLeft > 0)
                <button wire:click="startEdit" type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-blue-400/20 bg-blue-400/5 px-3 py-1.5 text-xs font-medium text-blue-400 transition hover:bg-blue-400/10 hover:text-blue-300">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    Edit
                    <span class="text-blue-400/40">({{ $editAttemptsLeft }} left)</span>
                </button>
                @else
                <span class="rounded-lg border border-white/[0.08] px-3 py-1.5 text-xs text-white/25">
                    Edit limit reached
                </span>
                @endif
            </div>

            {{-- OTP input --}}
            <form wire:submit="verify" class="flex flex-col items-center gap-5">
                <flux:otp wire:model="code" length="6" label="Verification Code" label:sr-only :error:icon="false"
                    error:class="text-center" class="mx-auto" />

                <flux:button variant="primary" type="submit" class="w-full rounded-3xl! py-3.5!">
                    <span wire:loading.remove wire:target="verify">Verify Phone</span>
                    <span wire:loading wire:target="verify">Verifying…</span>
                </flux:button>
            </form>

            {{-- Resend section --}}
            <div class="text-center border-t border-white/[0.06] pt-4">
                <template x-if="countdown > 0">
                    <div class="flex items-center justify-center gap-3">
                        <p class="text-sm text-white/40">Resend code in</p>
                        <div
                            class="inline-flex items-center justify-center w-10 h-10 rounded-full border border-white/10 bg-white/5 tabular-nums">
                            <span class="text-sm font-mono font-semibold text-white/70" x-text="countdown"></span>
                        </div>
                    </div>
                </template>

                <template x-if="countdown <= 0">
                    @if ($resendAttemptsLeft > 0)
                    <button wire:click="resend" wire:loading.attr="disabled" @click="countdown = 60; start()"
                        class="text-sm font-medium text-blue-400 transition hover:text-blue-300 disabled:opacity-50">
                        Resend code
                        <span class="text-blue-400/40 ml-1">({{ $resendAttemptsLeft }} left)</span>
                    </button>
                    @else
                    <p class="text-sm text-white/25">Resend limit reached. Contact support if needed.</p>
                    @endif
                </template>
            </div>
            @endif

        </div>

        <flux:subheading class="text-center text-white/60">
            Wrong account?
            <flux:link href="{{ route('auth.login') }}" class="text-white" wire:navigate>Sign out</flux:link>
        </flux:subheading>
    </div>
</div>