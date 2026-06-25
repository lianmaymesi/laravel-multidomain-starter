@php $title = 'Two-Factor Authentication'; @endphp

<div class="min-h-screen bg-zinc-950 flex items-center justify-center px-4 py-8">

    {{-- Background gradients --}}
    <div class="fixed inset-0 pointer-events-none">
        <div
            class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(59,130,246,0.18),transparent_35%),radial-gradient(circle_at_20%_80%,rgba(16,185,129,0.12),transparent_28%)]">
        </div>
    </div>

    <div class="relative mx-auto w-full max-w-md space-y-6">

        {{-- Header --}}
        <div class="inline-flex items-center gap-3 text-white transition hover:text-white/90">
            <span
                class="flex h-14 w-14 items-center justify-center rounded-2xl border border-white/10 bg-white/5 backdrop-blur-sm">
                <img src="{{ Vite::asset('resources/assets/images/logo.svg') }}" alt="{{ config('app.name') }}"
                    class="h-10 w-auto" />
            </span>
            <div>
                <flux:heading size="xl" class="text-3xl! font-semibold tracking-tight! text-white sm:text-4xl!">
                    Two-factor check
                </flux:heading>
                <flux:text class="hidden md:block text-sm leading-7 text-white/65">
                    Confirm it's you to finish signing in
                </flux:text>
            </div>
        </div>

        {{-- Card --}}
        <div
            class="rounded-[1.75rem] border border-white/10 bg-white/5 p-6 shadow-2xl shadow-black/20 backdrop-blur-md space-y-5">

            <div class="flex items-center gap-3 rounded-xl border border-blue-400/20 bg-blue-400/5 px-4 py-3">
                <span
                    class="flex size-10 shrink-0 items-center justify-center rounded-full border border-blue-400/20 bg-blue-400/10 text-blue-300">
                    <flux:icon.shield-check class="size-5" />
                </span>
                <div>
                    <p class="text-sm font-medium text-white">
                        {{ $usingRecovery ? 'Use a recovery code' : 'Authenticator code required' }}
                    </p>
                    <p class="text-sm text-white/50">
                        {{ $usingRecovery ? 'Enter one of your emergency recovery codes.' : 'Open your authenticator app and enter the 6-digit code.' }}
                    </p>
                </div>
            </div>

            <form wire:submit="verify" class="flex flex-col gap-5">
                @if (! $usingRecovery)
                    <flux:otp wire:model="code" length="6" label="Authenticator Code" label:sr-only :error:icon="false"
                        error:class="text-center" class="mx-auto" autofocus />
                @else
                    <flux:field>
                        <flux:label class="text-white/80">Recovery Code</flux:label>
                        <flux:input wire:model="code" type="text" placeholder="XXXXX-XXXXX" autocomplete="one-time-code"
                            class="font-mono tracking-widest" autofocus />
                        <flux:error name="code" />
                    </flux:field>
                @endif

                <flux:button type="submit" variant="primary" class="w-full rounded-3xl! py-3.5!">
                    <span wire:loading.remove>{{ $usingRecovery ? 'Use recovery code' : 'Verify code' }}</span>
                    <span wire:loading>Verifying…</span>
                </flux:button>
            </form>

            <div class="border-t border-white/[0.06] pt-5 text-center">
                <button type="button" wire:click="toggleRecovery"
                    class="text-sm font-medium text-blue-400 transition hover:text-blue-300">
                    {{ $usingRecovery ? 'Use authenticator app instead' : 'Use a recovery code instead' }}
                </button>
            </div>
        </div>

    </div>
</div>
