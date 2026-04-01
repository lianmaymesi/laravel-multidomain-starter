@php $title = 'Set New Password'; @endphp

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
                    Set new password
                </flux:heading>
                <flux:text class="hidden md:block text-sm leading-7 text-white/65">
                    Choose a strong password for your account
                </flux:text>
            </div>
        </div>

        {{-- Flash error (e.g. expired link) --}}
        @if (session('error'))
        <div class="rounded-xl border border-red-500/20 bg-red-500/10 px-4 py-3 text-sm text-red-400 text-center">
            {{ session('error') }}
        </div>
        @endif

        {{-- Card --}}
        <form wire:submit="resetPassword"
            class="space-y-4 rounded-[1.75rem] border border-white/10 bg-white/5 p-6 shadow-2xl shadow-black/20 backdrop-blur-md">

            {{-- Identity confirmed badge --}}
            <div class="flex items-center gap-3 rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3">
                <svg class="w-4 h-4 text-emerald-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                <p class="text-sm text-emerald-400">Identity verified — set your new password below</p>
            </div>

            <flux:field>
                <flux:label class="text-white/80">New Password</flux:label>
                <flux:input type="password" placeholder="Choose a strong password" wire:model="password"
                    autocomplete="new-password" viewable />
                <flux:error name="password" />
            </flux:field>

            <flux:field>
                <flux:label class="text-white/80">Confirm New Password</flux:label>
                <flux:input type="password" placeholder="Re-enter your new password" wire:model="password_confirmation"
                    autocomplete="new-password" viewable />
                <flux:error name="password_confirmation" />
            </flux:field>

            <div class="pt-1">
                <flux:button type="submit" variant="primary" class="w-full rounded-3xl! py-3.5!">
                    <span wire:loading.remove>Reset password</span>
                    <span wire:loading>Resetting…</span>
                </flux:button>
            </div>

        </form>

        <flux:subheading class="text-center text-white/60">
            Remembered it?
            <flux:link href="{{ route('auth.login') }}" class="text-white" wire:navigate>Sign in</flux:link>
        </flux:subheading>

    </div>
</div>