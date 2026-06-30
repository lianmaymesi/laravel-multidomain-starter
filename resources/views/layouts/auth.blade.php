<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/auth.css', 'resources/js/auth.js'])
</head>

<body class="min-h-screen bg-zinc-950 antialiased">
    {{ $slot }}

    @livewireScripts
    @fluxScripts
</body>

</html>
