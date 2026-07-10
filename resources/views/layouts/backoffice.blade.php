<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/backoffice.css', 'resources/js/backoffice.js'])

    @fluxAppearance
</head>

<body class="min-h-screen bg-zinc-950 text-white antialiased">

    <div class="flex min-h-screen">

        <flux:sidebar sticky class="border-e border-white/10 bg-zinc-950!">

            <flux:sidebar.header>
                <flux:sidebar.brand name="{{ config('app.name') }}" href="{{ route('backoffice.dashboard') }}">
                    <img src="{{ Vite::asset('resources/assets/images/logo.svg') }}" alt="{{ config('app.name') }}" />
                </flux:sidebar.brand>
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.item icon="chart-bar" href="{{ route('backoffice.dashboard') }}">
                    Dashboard
                </flux:sidebar.item>

                <flux:sidebar.group heading="Access Control">
                    <flux:sidebar.item icon="shield-check" href="{{ route('backoffice.roles.index') }}">
                        Roles
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="key" href="{{ route('backoffice.permissions.index') }}">
                        Permissions
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="users" href="{{ route('backoffice.users.index') }}">
                        Users
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:sidebar.spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="book-open" href="#" target="_blank">
                    Documentation
                </flux:sidebar.item>
                <flux:sidebar.item icon="code-bracket" href="#" target="_blank">
                    GitHub
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <flux:dropdown position="top" align="start" class="w-full">
                <flux:sidebar.profile name="{{ auth()->user()->name }}" />

                <flux:menu>
                    <flux:menu.item icon="user-circle" href="{{ route('account.index') }}">
                        Account
                    </flux:menu.item>
                    <flux:menu.separator />
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" variant="danger">
                            Logout
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>

        </flux:sidebar>

        <flux:main container class="flex-1">
            {{ $slot }}
        </flux:main>

    </div>

    @livewireScripts
    @fluxScripts
</body>

</html>
