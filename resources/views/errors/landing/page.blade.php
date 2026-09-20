<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app(App\Contracts\Languages::class)->currentDirection() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $meta['title'] }}</title>

    @vite(['resources/css/landing.css', 'resources/js/landing.js'])

    @fluxAppearance
</head>

<body class="min-h-screen bg-white dark:bg-zinc-950 text-zinc-900 dark:text-white antialiased">

    {{-- This page is landing's own — edit freely, independent of the shared
         auth/app/backoffice/account error pages in resources/views/errors/shared. --}}

    <header class="sticky top-0 z-40 border-b border-zinc-200 dark:border-white/6 bg-white/95 dark:bg-zinc-950/95 backdrop-blur-md">
        <div class="mx-auto flex h-13 max-w-7xl w-full items-center gap-4 px-4 sm:px-6 lg:px-8">
            <img src="{{ Vite::asset('resources/assets/images/logo.svg') }}" alt="{{ config('app.name') }}"
                class="h-6 w-auto" />
            <div class="h-4 w-px bg-zinc-200 dark:bg-white/10"></div>
            <span class="text-[11px] font-medium tracking-[0.12em] uppercase text-zinc-400 dark:text-white/30">{{ config('app.name') }}</span>
            <div class="ml-auto">
                <x-theme-switcher />
            </div>
        </div>
    </header>

    <div class="flex min-h-[calc(100vh-3.25rem)] items-center justify-center px-6 py-16">
        <div class="mx-auto w-full max-w-md text-center">
            @unless ($code === 'maintenance')
            <div class="text-sm font-medium tracking-[0.12em] uppercase text-zinc-400 dark:text-white/30">{{ __('Error') }} {{ $code }}</div>
            @endunless

            <flux:heading size="xl" class="mt-3">{{ $meta['title'] }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500 dark:text-white/50">{{ $message ?? $meta['description'] }}</flux:text>

            @unless ($code === 'maintenance')
            <div class="mt-8">
                <flux:button href="/" variant="primary">{{ __('Go back home') }}</flux:button>
            </div>
            @endunless
        </div>
    </div>

    @livewireScripts
    @fluxScripts
</body>

</html>
