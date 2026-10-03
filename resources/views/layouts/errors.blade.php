<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app(App\Contracts\Languages::class)->currentDirection() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/errors.css', 'resources/js/errors.js'])

    @fluxAppearance
</head>

<body class="min-h-screen bg-white dark:bg-zinc-950 text-zinc-900 dark:text-white antialiased">

    <div class="fixed inset-e-4 top-4 z-50">
        @module('language')<x-dynamic-component component="language::locale-switcher" />@endmodule
    </div>

    <div class="flex min-h-screen items-center justify-center px-6 py-16">
        <div class="mx-auto w-full max-w-md text-center">
            <img src="{{ Vite::asset('resources/assets/images/logo.svg') }}" alt="{{ config('app.name') }}"
                class="mx-auto h-6 w-auto" />

            @yield('content')
        </div>
    </div>

    @livewireScripts
    @fluxScripts
</body>

</html>
