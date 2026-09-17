@php $title = __('Set New Password'); @endphp

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
                <p class="mb-1 text-[10px] tracking-[0.25em] uppercase text-blue-500 dark:text-blue-400/55">{{ __('Password
                    recovery') }}</p>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ __('Set new password') }}</h1>
                <p class="mt-1 text-sm text-zinc-500 dark:text-white/40">{{ __('Choose a strong password for your account') }}</p>
            </div>

            {{-- Session error --}}
            @if (session('error'))
            <div class="mb-4 border border-red-500/20 bg-red-500/6 px-4 py-3 text-center text-sm text-red-400">
                {{ session('error') }}
            </div>
            @endif

            <flux:card class="p-5!">
                <form wire:submit="resetPassword" class="space-y-4">

                    {{-- Identity verified badge --}}
                    <div class="flex items-center gap-3 border border-emerald-500/20 bg-emerald-500/6 px-4 py-3">
                        <flux:icon.shield-check class="size-4 shrink-0 text-emerald-400" />
                        <p class="text-sm text-emerald-400">{{ __('Identity verified — set your new password below') }}</p>
                    </div>

                    <flux:field>
                        <flux:label>{{ __('New Password') }}</flux:label>
                        <flux:input type="password" placeholder="{{ __('Choose a strong password') }}" wire:model="password"
                            autocomplete="new-password" viewable />
                        <flux:error name="password" />
                    </flux:field>

                    <flux:field>
                        <flux:label>{{ __('Confirm New Password') }}</flux:label>
                        <flux:input type="password" placeholder="{{ __('Re-enter your new password') }}"
                            wire:model="password_confirmation" autocomplete="new-password" viewable />
                        <flux:error name="password_confirmation" />
                    </flux:field>

                    <div class="pt-1">
                        <flux:button type="submit" variant="primary" class="w-full">
                            <span wire:loading.remove>{{ __('Reset password') }}</span>
                            <span wire:loading>{{ __('Resetting…') }}</span>
                        </flux:button>
                    </div>

                </form>
            </flux:card>

            <p class="mt-5 text-center text-xs text-zinc-400 dark:text-white/35">
                {{ __('Remembered it?') }}
                <flux:link href="{{ route('auth.login') }}" wire:navigate
                    class="text-zinc-600! dark:text-white/60! hover:text-zinc-900! dark:hover:text-white!">
                    {{ __('Sign in') }}
                </flux:link>
            </p>

        </div>
    </div>

</div>