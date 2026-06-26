@php $title = 'Verify Email Change'; @endphp

<div class="min-h-screen bg-zinc-950 flex items-center justify-center px-4 py-8">

    <div class="fixed inset-0 pointer-events-none">
        <div
            class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(59,130,246,0.18),transparent_35%),radial-gradient(circle_at_20%_80%,rgba(16,185,129,0.12),transparent_28%)]">
        </div>
    </div>

    <div class="relative mx-auto w-full max-w-md space-y-6">

        <div class="inline-flex items-center gap-3 text-white">
            <span
                class="flex h-14 w-14 items-center justify-center rounded-2xl border border-white/10 bg-white/5 backdrop-blur-sm">
                <img src="{{ Vite::asset('resources/assets/images/logo.svg') }}" alt="{{ config('app.name') }}"
                    class="h-10 w-auto" />
            </span>
            <flux:heading size="xl" class="!text-3xl font-semibold !tracking-tight text-white sm:!text-4xl">
                Email Verification
            </flux:heading>
        </div>

        <div
            class="rounded-[1.75rem] border border-white/10 bg-white/5 p-8 shadow-2xl shadow-black/20 backdrop-blur-md">

            @if ($status === 'success')

            <div class="space-y-5 text-center">
                <div class="flex justify-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-500/15">
                        <flux:icon.check-circle class="size-8 text-emerald-400" />
                    </div>
                </div>
                <div>
                    <p class="text-lg font-semibold text-white">Email updated</p>
                    <p class="mt-1 text-sm text-white/50">Your email address has been verified and is now active.</p>
                </div>
                <flux:button href="{{ route('account.index') }}" variant="primary" class="w-full rounded-3xl! py-3.5!">
                    Go to account
                    <flux:icon.arrow-right class="ml-1 size-4" />
                </flux:button>
            </div>

            @elseif ($status === 'expired')

            <div class="space-y-5 text-center">
                <div class="flex justify-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-500/15">
                        <flux:icon.clock class="size-8 text-amber-400" />
                    </div>
                </div>
                <div>
                    <p class="text-lg font-semibold text-white">Link expired</p>
                    <p class="mt-1 text-sm text-white/50">
                        The verification link for
                        @if ($pendingEmail)
                        <span class="text-white/75">{{ $pendingEmail }}</span>
                        @endif
                        has expired (links are valid for 48 hours).
                    </p>
                </div>
                <p class="text-sm text-white/40">
                    Go back to your account and request a new verification link.
                </p>
                <flux:button href="{{ route('account.index') }}" variant="ghost" class="w-full rounded-3xl!">
                    Back to account
                </flux:button>
            </div>

            @else

            <div class="space-y-5 text-center">
                <div class="flex justify-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-red-500/15">
                        <flux:icon.x-circle class="size-8 text-red-400" />
                    </div>
                </div>
                <div>
                    <p class="text-lg font-semibold text-white">Invalid link</p>
                    <p class="mt-1 text-sm text-white/50">
                        This verification link is invalid or has already been used.
                    </p>
                </div>
                <flux:button href="{{ route('account.index') }}" variant="ghost" class="w-full rounded-3xl!">
                    Back to account
                </flux:button>
            </div>

            @endif

        </div>

    </div>
</div>