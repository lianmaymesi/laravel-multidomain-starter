@php $title = 'Forgot Password'; @endphp

<div class="min-h-screen bg-zinc-950 flex items-center justify-center px-4 py-8">

    <div class="fixed inset-0 pointer-events-none">
        <div
            class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(59,130,246,0.18),transparent_35%),radial-gradient(circle_at_20%_80%,rgba(16,185,129,0.12),transparent_28%)]">
        </div>
    </div>

    <div class="relative mx-auto w-full max-w-md space-y-6">

        <div class="inline-flex items-center gap-3 text-white transition hover:text-white/90">
            <span
                class="flex h-14 w-14 items-center justify-center rounded-2xl border border-white/10 bg-white/5 backdrop-blur-sm">
                <img src="{{ Vite::asset('resources/assets/images/logo.svg') }}" alt="{{ config('app.name') }}"
                    class="h-10 w-auto" />
            </span>
            <div>
                <flux:heading size="xl" class="text-3xl! font-semibold tracking-tight! text-white sm:text-4xl!">
                    Reset password
                </flux:heading>
                <flux:text class="hidden md:block text-sm leading-7 text-white/65">
                    We'll send a verification code to your phone
                </flux:text>
            </div>
        </div>

        <div
            class="rounded-[1.75rem] border border-white/10 bg-white/5 p-6 shadow-2xl shadow-black/20 backdrop-blur-md space-y-5">

            @if (! $otpSent)

            <form wire:submit="sendOtp" class="space-y-4">

                <flux:field>
                    <flux:label class="text-white/80">Phone Number</flux:label>
                    <flux:input mask="99999-99999" placeholder="98765-43210" wire:model.live="phone" />
                    <flux:error name="phone" />
                </flux:field>

                <flux:button type="submit" variant="primary" class="w-full rounded-3xl! py-3.5!">
                    Send reset code
                </flux:button>

            </form>

            @else

            <div
                class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-400 text-center">
                {{ session('status') ?? 'If this number is registered, you will receive a code shortly.' }}
            </div>

            <flux:button href="{{ route('auth.reset-password') }}" wire:navigate variant="primary"
                class="w-full rounded-3xl! py-3.5!">
                Enter reset code →
            </flux:button>

            @endif

        </div>

        <flux:subheading class="text-center text-white/60">
            Remembered it?
            <flux:link href="{{ route('auth.login') }}" class="text-white" wire:navigate>Sign in</flux:link>
        </flux:subheading>

    </div>
</div>
