<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app(App\Services\LanguageService::class)->currentDirection() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/landing.css', 'resources/js/landing.js'])

    @fluxAppearance
</head>

<body class="min-h-screen bg-white dark:bg-zinc-950 text-zinc-900 dark:text-white antialiased">

    {{-- Sticky header --}}
    <header class="sticky top-0 z-40 border-b border-zinc-200 dark:border-white/6 bg-white/95 dark:bg-zinc-950/95 backdrop-blur-md">
        <div class="mx-auto flex h-13 max-w-7xl w-full items-center gap-4 px-4 sm:px-6 lg:px-8">
            <img src="{{ Vite::asset('resources/assets/images/logo.svg') }}" alt="{{ config('app.name') }}"
                class="h-6 w-auto" />
            <div class="h-4 w-px bg-zinc-200 dark:bg-white/10"></div>
            <span class="text-[11px] font-medium tracking-[0.12em] uppercase text-zinc-400 dark:text-white/30">{{ config('app.name') }}</span>
            <div class="ml-auto">
                <x-theme-switcher />
                <x-locale-switcher />
            </div>
        </div>
    </header>

    {{ $slot }}

    @livewireScripts
    @fluxScripts
</body>

</html>
