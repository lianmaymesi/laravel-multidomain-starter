@php $title = __('Verify Email'); @endphp

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
            <div class="mb-6 border-s-[3px] border-blue-500 ps-4">
                <p class="mb-1 text-[10px] tracking-[0.25em] uppercase text-blue-500 dark:text-blue-400/55">{{ __('Email
                    verification') }}</p>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('Verify your email') }}</h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-white/40">{{ __('Enter the 6-digit code we sent you') }}</p>
            </div>

            <flux:card class="p-5! space-y-5">

                {{-- Flash status --}}
                @if (session('status'))
                <div
                    class="border border-emerald-500/20 bg-emerald-500/6 px-4 py-3 text-center text-sm text-emerald-400">
                    {{ session('status') }}
                </div>
                @endif

                {{-- Deadline warning --}}
                @if ($daysLeft > 0)
                <div class="border border-amber-500/20 bg-amber-500/6 px-4 py-3 text-center text-sm text-amber-400">
                    {!! __('Verify within <strong>:days :plural</strong> to avoid access restrictions.', ['days' => $daysLeft, 'plural' => Str::plural('day', $daysLeft)]) !!}
                </div>
                @endif

                {{-- Email display --}}
                <div
                    class="flex items-center gap-3 border border-zinc-200 dark:border-white/10 bg-zinc-50 dark:bg-white/4 px-4 py-3">
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center border border-zinc-200 dark:border-white/10 bg-zinc-100 dark:bg-white/5">
                        <flux:icon.envelope class="size-4 text-zinc-500 dark:text-white/50" />
                    </div>
                    <div>
                        <p class="mb-0.5 text-xs text-zinc-400 dark:text-white/35">{{ __('Code sent to') }}</p>
                        <p class="text-sm font-medium text-zinc-800 dark:text-white/85">{{ auth()->user()?->email }}</p>
                    </div>
                </div>

                {{-- OTP form --}}
                <form wire:submit="verify" class="flex flex-col items-center gap-5">
                    <flux:otp wire:model="code" length="6" label="{{ __('Verification Code') }}" label:sr-only :error:icon="false"
                        error:class="text-center" class="mx-auto" />

                    <flux:button variant="primary" type="submit" class="w-full">
                        <span wire:loading.remove wire:target="verify">{{ __('Verify Email') }}</span>
                        <span wire:loading wire:target="verify">{{ __('Verifying…') }}</span>
                    </flux:button>
                </form>

                {{-- Resend --}}
                <div class="border-t border-zinc-200 dark:border-white/6 pt-4 text-center">
                    <button wire:click="resend" wire:loading.attr="disabled" wire:target="resend"
                        class="text-sm font-medium text-blue-600 dark:text-blue-400 transition hover:text-blue-700 dark:hover:text-blue-300 disabled:opacity-50">
                        <span wire:loading.remove wire:target="resend">{{ __('Resend code') }}</span>
                        <span wire:loading wire:target="resend">{{ __('Sending…') }}</span>
                    </button>
                </div>

            </flux:card>

            {{-- Verify later --}}
            @if ($daysLeft > 0)
            <p class="mt-5 text-center text-xs text-zinc-400 dark:text-white/30">
                <a href="{{ auth()->user()?->redirect() }}"
                    class="underline underline-offset-2 transition-colors hover:text-zinc-500 dark:hover:text-white/50">
                    {{ __('Verify later (:days :plural remaining)', ['days' => $daysLeft, 'plural' => Str::plural('day', $daysLeft)]) }}
                </a>
            </p>
            @endif

        </div>
    </div>

</div>