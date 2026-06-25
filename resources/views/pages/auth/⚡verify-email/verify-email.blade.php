@php $title = 'Verify Email'; @endphp

<div class="min-h-screen bg-zinc-950 flex items-center justify-center px-4 py-8">

    {{-- Background gradients --}}
    <div class="fixed inset-0 pointer-events-none">
        <div
            class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(59,130,246,0.18),transparent_35%),radial-gradient(circle_at_20%_80%,rgba(16,185,129,0.12),transparent_28%)]">
        </div>
    </div>

    <div class="relative mx-auto w-full max-w-md space-y-6">

        {{-- Header --}}
        <div class="inline-flex items-center gap-3 text-white">
            <span
                class="flex h-14 w-14 items-center justify-center rounded-2xl border border-white/10 bg-white/5 backdrop-blur-sm">
                <img src="{{ Vite::asset('resources/assets/images/logo.svg') }}" alt="{{ config('app.name') }}"
                    class="h-10 w-auto" />
            </span>
            <div>
                <flux:heading size="xl" class="text-3xl! font-semibold tracking-tight! text-white sm:text-4xl!">
                    Verify your email
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

            {{-- Deadline warning --}}
            @if ($daysLeft > 0)
                <div
                    class="rounded-xl border border-amber-500/20 bg-amber-500/10 px-4 py-3 text-sm text-amber-400 text-center">
                    Verify within <strong>{{ $daysLeft }} {{ Str::plural('day', $daysLeft) }}</strong> to avoid access
                    restrictions.
                </div>
            @endif

            {{-- Email display --}}
            <div class="rounded-xl border border-white/10 bg-white/[0.04] px-4 py-3 flex items-center gap-3">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg border border-white/10 bg-white/5">
                    <flux:icon.envelope class="size-4 text-white/50" />
                </div>
                <div>
                    <p class="text-xs text-white/35 mb-0.5">Code sent to</p>
                    <p class="text-sm font-medium text-white/85 tracking-wide">
                        {{ auth()->user()?->email }}
                    </p>
                </div>
            </div>

            {{-- OTP input --}}
            <form wire:submit="verify" class="flex flex-col items-center gap-5">
                <flux:otp wire:model="code" length="6" label="Verification Code" label:sr-only :error:icon="false"
                    error:class="text-center" class="mx-auto" />

                <flux:button variant="primary" type="submit" class="w-full rounded-3xl! py-3.5!">
                    <span wire:loading.remove wire:target="verify">Verify Email</span>
                    <span wire:loading wire:target="verify">Verifying…</span>
                </flux:button>
            </form>

            {{-- Resend --}}
            <div class="text-center border-t border-white/[0.06] pt-4">
                <button wire:click="resend" wire:loading.attr="disabled" wire:target="resend"
                    class="text-sm font-medium text-blue-400 transition hover:text-blue-300 disabled:opacity-50">
                    <span wire:loading.remove wire:target="resend">Resend code</span>
                    <span wire:loading wire:target="resend">Sending…</span>
                </button>
            </div>

        </div>

        {{-- Verify later --}}
        @if ($daysLeft > 0)
            <p class="text-center text-xs text-white/30">
                <a href="{{ auth()->user()?->redirectSubdomain() }}"
                    class="underline underline-offset-2 hover:text-white/50 transition-colors">
                    Verify later ({{ $daysLeft }} {{ Str::plural('day', $daysLeft) }} remaining)
                </a>
            </p>
        @endif

    </div>
</div>
