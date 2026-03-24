<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/auth.css', 'resources/js/auth.js'])

    @fluxAppearance
</head>

<body class="dark:bg-linear-to-b min-h-screen bg-white antialiased dark:from-zinc-950 dark:to-zinc-900">
    {{ $slot }}

    @livewireScripts
    @fluxScripts
</body>

</html>