<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ($title ?? 'Backoffice') . ' · ' . config('app.name') }}</title>
    @vite(['resources/css/backoffice.css', 'resources/js/backoffice.js'])
    @fluxAppearance
</head>

<body class="min-h-screen bg-zinc-950 text-white antialiased">

    {{-- Sticky header --}}
    <header class="sticky top-0 z-40 border-b border-white/6 bg-zinc-950/95 backdrop-blur-md">
        <div class="mx-auto flex h-13 max-w-7xl w-full items-center justify-between px-4 sm:px-6 lg:px-8">

            <div class="flex items-center gap-4">
                <img src="{{ Vite::asset('resources/assets/images/logo.svg') }}" alt="{{ config('app.name') }}"
                    class="h-6 w-auto" />
                <div class="h-4 w-px bg-white/10"></div>
                <span class="text-[11px] font-medium tracking-[0.12em] uppercase text-white/30">Backoffice</span>
            </div>

            <div class="flex items-center gap-3">
                <flux:dropdown position="bottom" align="end">
                    <button type="button" class="flex items-center gap-2">
                        <flux:avatar size="sm" name="{{ auth()->user()->name }}" />
                    </button>

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
            </div>

        </div>
    </header>

    {{-- Mobile nav --}}
    <div class="border-b border-white/6 bg-zinc-950 lg:hidden sticky top-13.25 z-30">
        <div class="mx-auto max-w-7xl overflow-x-auto px-4">
            <nav class="flex">
                @php
                $mobileItems = [
                ['label' => 'Dashboard', 'route' => 'backoffice.dashboard'],
                ['label' => 'Roles', 'route' => 'backoffice.roles.index'],
                ['label' => 'Permissions', 'route' => 'backoffice.permissions.index'],
                ['label' => 'Users', 'route' => 'backoffice.users.index'],
                ];
                @endphp
                @foreach ($mobileItems as $item)
                @php $active = request()->routeIs($item['route']); @endphp
                @if (Route::has($item['route']))
                <a href="{{ route($item['route']) }}" wire:navigate
                    class="shrink-0 border-b-2 px-4 py-3 text-sm font-medium transition-colors
                                {{ $active ? 'border-blue-400 text-white' : 'border-transparent text-white/40 hover:border-white/20 hover:text-white/65' }}">
                    {{ $item['label'] }}
                </a>
                @else
                <span class="shrink-0 cursor-not-allowed border-b-2 border-transparent px-4 py-3 text-sm text-white/20">
                    {{ $item['label'] }}
                </span>
                @endif
                @endforeach
            </nav>
        </div>
    </div>

    {{-- Page body --}}
    <div class="mx-auto max-w-7xl w-full px-4 pt-8 sm:px-6 lg:px-8">
        <div class="flex gap-10">

            {{-- Sidebar --}}
            <aside class="hidden w-52 shrink-0 lg:flex lg:flex-col top-21.25 h-[calc(100vh-95px)] sticky">
                <nav class="min-h-0 flex-1 overflow-y-auto space-y-px scrollbar-none [&::-webkit-scrollbar]:hidden">

                    @php $dashboardActive = request()->routeIs('backoffice.dashboard'); @endphp
                    <a href="{{ route('backoffice.dashboard') }}" wire:navigate
                        class="group flex items-center gap-3 border-l-2 px-4 py-2.5 text-sm transition-colors
                            {{ $dashboardActive ? 'border-blue-400 bg-blue-500/7 text-white' : 'border-transparent text-white/40 hover:border-white/15 hover:bg-white/3 hover:text-white/75' }}">
                        <flux:icon.chart-bar
                            class="size-4 shrink-0 {{ $dashboardActive ? 'text-blue-400' : 'text-white/25 group-hover:text-white/50' }}" />
                        Dashboard
                    </a>

                    <div class="mt-4 mb-1 px-4 text-[10px] font-medium tracking-[0.12em] uppercase text-white/20">
                        Access Control
                    </div>

                    @php $rolesActive = request()->routeIs('backoffice.roles.index'); @endphp
                    <a href="{{ route('backoffice.roles.index') }}" wire:navigate
                        class="group flex items-center gap-3 border-l-2 px-4 py-2.5 text-sm transition-colors
                            {{ $rolesActive ? 'border-blue-400 bg-blue-500/7 text-white' : 'border-transparent text-white/40 hover:border-white/15 hover:bg-white/3 hover:text-white/75' }}">
                        <flux:icon.shield-check
                            class="size-4 shrink-0 {{ $rolesActive ? 'text-blue-400' : 'text-white/25 group-hover:text-white/50' }}" />
                        Roles
                    </a>

                    @php $permissionsActive = request()->routeIs('backoffice.permissions.index'); @endphp
                    <a href="{{ route('backoffice.permissions.index') }}" wire:navigate
                        class="group flex items-center gap-3 border-l-2 px-4 py-2.5 text-sm transition-colors
                            {{ $permissionsActive ? 'border-blue-400 bg-blue-500/7 text-white' : 'border-transparent text-white/40 hover:border-white/15 hover:bg-white/3 hover:text-white/75' }}">
                        <flux:icon.key
                            class="size-4 shrink-0 {{ $permissionsActive ? 'text-blue-400' : 'text-white/25 group-hover:text-white/50' }}" />
                        Permissions
                    </a>

                    @php $usersActive = request()->routeIs('backoffice.users.index'); @endphp
                    <a href="{{ route('backoffice.users.index') }}" wire:navigate
                        class="group flex items-center gap-3 border-l-2 px-4 py-2.5 text-sm transition-colors
                            {{ $usersActive ? 'border-blue-400 bg-blue-500/7 text-white' : 'border-transparent text-white/40 hover:border-white/15 hover:bg-white/3 hover:text-white/75' }}">
                        <flux:icon.users
                            class="size-4 shrink-0 {{ $usersActive ? 'text-blue-400' : 'text-white/25 group-hover:text-white/50' }}" />
                        Users
                    </a>

                </nav>

                <div class="shrink-0 border-t border-white/6 pt-2">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="flex w-full items-center gap-3 border-l-2 border-transparent px-4 py-2.5 text-sm text-white/30 transition-colors hover:border-red-500/40 hover:bg-red-500/5 hover:text-red-400">
                            <flux:icon.arrow-right-start-on-rectangle class="size-4 shrink-0" />
                            Sign out
                        </button>
                    </form>
                </div>

            </aside>

            {{-- Main content --}}
            <main class="min-w-0 flex-1 pb-8">
                {{ $slot }}
            </main>

        </div>
    </div>

    @livewireScripts
    @fluxScripts
</body>

</html>
