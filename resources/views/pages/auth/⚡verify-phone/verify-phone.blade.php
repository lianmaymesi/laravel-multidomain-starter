@php $title = 'Verify Phone'; @endphp

<div class="flex min-h-screen flex-col bg-white dark:bg-zinc-950" x-data="{
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

    {{-- Top bar --}}
    <div class="flex h-12 shrink-0 items-center border-b border-zinc-200 dark:border-white/6 px-6">
        <div class="flex items-center justify-between w-full">
            <div class="flex items-center gap-3">
                <img src="{{ Vite::asset('resources/assets/images/logo.svg') }}" alt="{{ config('app.name') }}"
                    class="h-7 w-auto" />
                <span class="text-xs font-semibold tracking-tight text-zinc-500 dark:text-white/50">{{
                    config('app.name') }}</span>
            </div>
            <x-theme-switcher />
        </div>
    </div>

    {{-- Content --}}
    <div class="flex flex-1 items-center justify-center px-4 py-12">
        <div class="w-full max-w-sm">

            {{-- Heading block --}}
            <div class="mb-6 border-l-[3px] border-blue-500 pl-4">
                <p class="mb-1 text-[10px] tracking-[0.25em] uppercase text-blue-500 dark:text-blue-400/55">Phone
                    verification</p>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Verify your phone</h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-white/40">Enter the 6-digit code we sent you</p>
            </div>

            <flux:card class="p-5! space-y-5">

                {{-- Flash status --}}
                @if (session('status'))
                <div
                    class="border border-emerald-500/20 bg-emerald-500/6 px-4 py-3 text-center text-sm text-emerald-400">
                    {{ session('status') }}
                </div>
                @endif

                {{-- Phone display or edit form --}}
                @if ($editingPhone)

                <form wire:submit="updatePhone" class="space-y-3">
                    <p class="text-sm text-zinc-500 dark:text-white/50 text-center">Enter your updated phone number</p>
                    <div class="flex gap-2">
                        @if (config('multidomain.phone_country_mode') === 'multi')
                        <div class="w-24">
                            <flux:input wire:model="newCountryCode" placeholder="+91" class="text-center" />
                        </div>
                        @endif
                        <div class="flex-1">
                            <flux:input wire:model="newPhone" mask="99999-99999" placeholder="98765-43210" />
                        </div>
                    </div>
                    @if (config('multidomain.phone_country_mode') === 'multi')
                    <flux:error name="newCountryCode" />
                    @endif
                    <flux:error name="newPhone" />
                    <div class="flex gap-2 pt-1">
                        <flux:button type="button" wire:click="cancelEdit" class="flex-1">Cancel</flux:button>
                        <flux:button type="submit" variant="primary" class="flex-1">
                            <span wire:loading.remove wire:target="updatePhone">Update & Resend</span>
                            <span wire:loading wire:target="updatePhone">Saving…</span>
                        </flux:button>
                    </div>
                </form>

                @else

                {{-- Phone display --}}
                <div
                    class="flex items-center justify-between border border-zinc-200 dark:border-white/10 bg-zinc-50 dark:bg-white/4 px-4 py-3">
                    <div>
                        <p class="mb-0.5 text-xs text-zinc-400 dark:text-white/35">Code sent to</p>
                        <p class="text-sm font-medium tracking-wide text-zinc-800 dark:text-white/85">
                            {{ auth()->user()?->country_code }}
                            <span class="text-zinc-500 dark:text-white/40">••••••</span>{{ substr(auth()->user()?->phone
                            ?? '', -3) }}
                        </p>
                    </div>

                    @if ($editAttemptsLeft > 0)
                    <button wire:click="startEdit" type="button"
                        class="flex items-center gap-1.5 border border-blue-400/20 bg-blue-50 dark:bg-blue-400/5 px-3 py-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 transition hover:bg-blue-100 dark:hover:bg-blue-400/10 hover:text-blue-700 dark:hover:text-blue-300">
                        <flux:icon.pencil class="size-3.5" />
                        Edit
                        <span class="text-blue-500 dark:text-blue-400/40">({{ $editAttemptsLeft }})</span>
                    </button>
                    @else
                    <span
                        class="border border-zinc-200 dark:border-white/8 px-3 py-1.5 text-xs text-zinc-400 dark:text-white/25">
                        Edit limit reached
                    </span>
                    @endif
                </div>

                {{-- OTP form --}}
                <form wire:submit="verify" class="flex flex-col items-center gap-5">
                    <flux:otp wire:model="code" length="6" label="Verification Code" label:sr-only :error:icon="false"
                        error:class="text-center" class="mx-auto" />

                    <flux:button variant="primary" type="submit" class="w-full">
                        <span wire:loading.remove wire:target="verify">Verify Phone</span>
                        <span wire:loading wire:target="verify">Verifying…</span>
                    </flux:button>
                </form>

                {{-- Resend section --}}
                <div class="border-t border-zinc-200 dark:border-white/6 pt-4 text-center">
                    <template x-if="countdown > 0">
                        <div class="flex items-center justify-center gap-3">
                            <p class="text-sm text-zinc-500 dark:text-white/40">Resend in</p>
                            <div
                                class="inline-flex h-8 w-8 items-center justify-center border border-zinc-200 dark:border-white/10 bg-zinc-100 dark:bg-white/5">
                                <span
                                    class="font-mono text-sm font-semibold text-zinc-700 dark:text-white/70 tabular-nums"
                                    x-text="countdown"></span>
                            </div>
                        </div>
                    </template>

                    <template x-if="countdown <= 0">
                        @if ($resendAttemptsLeft > 0)
                        <button wire:click="resend" wire:loading.attr="disabled" @click="countdown = 60; start()"
                            class="text-sm font-medium text-blue-600 dark:text-blue-400 transition hover:text-blue-700 dark:hover:text-blue-300 disabled:opacity-50">
                            Resend code
                            <span class="ml-1 text-blue-500 dark:text-blue-400/40">({{ $resendAttemptsLeft }}
                                left)</span>
                        </button>
                        @else
                        <p class="text-sm text-zinc-400 dark:text-white/25">Resend limit reached. Contact support if
                            needed.</p>
                        @endif
                    </template>
                </div>

                @endif

            </flux:card>

            <p class="mt-5 text-center text-xs text-zinc-400 dark:text-white/35">
                Wrong account?
                <flux:link href="{{ route('auth.login') }}" wire:navigate
                    class="text-zinc-600! dark:text-white/60! hover:text-zinc-900! dark:hover:text-white!">
                    Sign out
                </flux:link>
            </p>

        </div>
    </div>

</div>