@php $title = 'Verify Email Change'; @endphp

<div class="flex min-h-screen flex-col bg-white dark:bg-zinc-950">

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
                <p class="mb-1 text-[10px] tracking-[0.25em] uppercase text-blue-500 dark:text-blue-400/55">Account
                    security</p>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Email verification</h1>
            </div>

            <flux:card class="p-5!">

                @if ($status === 'success')

                <div class="space-y-5 text-center">
                    <div class="flex justify-center">
                        <div
                            class="flex h-14 w-14 items-center justify-center border border-emerald-500/20 bg-emerald-500/8">
                            <flux:icon.check-circle class="size-7 text-emerald-400" />
                        </div>
                    </div>
                    <div>
                        <p class="text-base font-semibold text-zinc-900 dark:text-white">Email updated</p>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-white/50">Your email address has been verified
                            and is now active.</p>
                    </div>
                    <flux:button href="{{ route('account.index') }}" variant="primary" class="w-full">
                        Go to account
                        <flux:icon.arrow-right class="ml-1 size-4" />
                    </flux:button>
                </div>

                @elseif ($status === 'expired')

                <div class="space-y-5 text-center">
                    <div class="flex justify-center">
                        <div
                            class="flex h-14 w-14 items-center justify-center border border-amber-500/20 bg-amber-500/8">
                            <flux:icon.clock class="size-7 text-amber-400" />
                        </div>
                    </div>
                    <div>
                        <p class="text-base font-semibold text-zinc-900 dark:text-white">Link expired</p>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-white/50">
                            The verification link for
                            @if ($pendingEmail)
                            <span class="text-zinc-700 dark:text-white/75">{{ $pendingEmail }}</span>
                            @endif
                            has expired (links are valid for 48 hours).
                        </p>
                    </div>
                    <p class="text-sm text-zinc-500 dark:text-white/40">
                        Go back to your account and request a new verification link.
                    </p>
                    <flux:button href="{{ route('account.index') }}" variant="ghost" class="w-full">
                        Back to account
                    </flux:button>
                </div>

                @else

                <div class="space-y-5 text-center">
                    <div class="flex justify-center">
                        <div class="flex h-14 w-14 items-center justify-center border border-red-500/20 bg-red-500/8">
                            <flux:icon.x-circle class="size-7 text-red-400" />
                        </div>
                    </div>
                    <div>
                        <p class="text-base font-semibold text-zinc-900 dark:text-white">Invalid link</p>
                        <p class="mt-1 text-sm text-zinc-500 dark:text-white/50">
                            This verification link is invalid or has already been used.
                        </p>
                    </div>
                    <flux:button href="{{ route('account.index') }}" variant="ghost" class="w-full">
                        Back to account
                    </flux:button>
                </div>

                @endif

            </flux:card>

        </div>
    </div>

</div>