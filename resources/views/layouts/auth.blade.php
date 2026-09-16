<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app(App\Services\LanguageService::class)->currentDirection() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/auth.css', 'resources/js/auth.js'])

    @fluxAppearance
</head>

<body class="min-h-screen bg-white dark:bg-zinc-950 antialiased">

    <div class="fixed inset-e-4 top-4 z-50">
        <x-locale-switcher />
    </div>

    {{ $slot }}

    @livewireScripts
    @fluxScripts
</body>

</html>