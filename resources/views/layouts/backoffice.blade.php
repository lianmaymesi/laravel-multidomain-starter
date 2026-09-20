<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app(App\Contracts\Languages::class)->currentDirection() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ($title ?? __('Backoffice')) . ' · ' . config('app.name') }}</title>
    @vite(['resources/css/backoffice.css', 'resources/js/backoffice.js'])
    @fluxAppearance
</head>

<body class="min-h-screen bg-white dark:bg-zinc-950 text-zinc-900 dark:text-white antialiased">

    {{-- Sticky header --}}
    <header class="sticky top-0 z-40 border-b border-zinc-200 dark:border-white/6 bg-white/95 dark:bg-zinc-950/95 backdrop-blur-md">
        <div class="mx-auto flex h-13 max-w-7xl w-full items-center justify-between px-4 sm:px-6 lg:px-8">

            <div class="flex items-center gap-4">
                <img src="{{ Vite::asset('resources/assets/images/logo.svg') }}" alt="{{ config('app.name') }}"
                    class="h-6 w-auto" />
                <div class="h-4 w-px bg-zinc-200 dark:bg-white/10"></div>
                <span class="text-[11px] font-medium tracking-[0.12em] uppercase text-zinc-400 dark:text-white/30">{{ __('Backoffice') }}</span>
            </div>

            <div class="flex items-center gap-2">
                <x-theme-switcher />
                <flux:dropdown position="bottom" align="end">
                    <button type="button" class="flex items-center gap-2">
                        <flux:avatar size="sm" name="{{ auth()->user()->name }}" />
                    </button>

                    <flux:menu class="min-w-56 dark:border-white/10! dark:bg-zinc-900! shadow-lg shadow-black/40">
                        <flux:menu.item icon="user-circle" href="{{ route('account.index') }}" class="dark:data-active:bg-white/8!">
                            {{ __('Account') }}
                        </flux:menu.item>
                        <flux:menu.separator class="dark:bg-white/10!" />
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" variant="danger">
                                {{ __('Logout') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </div>

        </div>
    </header>

    {{-- Mobile nav --}}
    <div class="border-b border-zinc-200 dark:border-white/6 bg-white dark:bg-zinc-950 lg:hidden sticky top-13.25 z-30">
        <div class="mx-auto max-w-7xl overflow-x-auto px-4">
            <nav class="flex">
                @php
                $mobileItems = [
                ['label' => __('Dashboard'), 'route' => 'backoffice.dashboard'],
                ['label' => __('Roles'), 'route' => 'backoffice.roles.index'],
                ['label' => __('Permissions'), 'route' => 'backoffice.permissions.index'],
                ['label' => __('Users'), 'route' => 'backoffice.users.index'],
                ];

                // Module nav items opt in to the mobile bar with 'mobile' => true.
                foreach (\App\Support\Modules\Module::contributions('backoffice.nav') as $item) {
                    if (($item['mobile'] ?? false) && (! isset($item['visible']) || $item['visible']()) && (! isset($item['permission']) || Gate::any((array) $item['permission']))) {
                        $mobileItems[] = ['label' => __($item['label']), 'route' => $item['route']];
                    }
                }
                @endphp
                @foreach ($mobileItems as $item)
                @php $active = request()->routeIs($item['route']); @endphp
                @if (Route::has($item['route']))
                <a href="{{ route($item['route']) }}" wire:navigate
                    class="shrink-0 border-b-2 px-4 py-3 text-sm font-medium transition-colors
                                {{ $active ? 'border-blue-400 text-zinc-900 dark:text-white' : 'border-transparent text-zinc-500 dark:text-white/40 hover:border-zinc-300 dark:hover:border-white/20 hover:text-zinc-600 dark:hover:text-white/65' }}">
                    {{ $item['label'] }}
                </a>
                @else
                <span class="shrink-0 cursor-not-allowed border-b-2 border-transparent px-4 py-3 text-sm text-zinc-300 dark:text-white/20">
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
                        class="group flex items-center gap-3 border-s-2 px-4 py-2.5 text-sm transition-colors
                            {{ $dashboardActive ? 'border-blue-400 bg-blue-50 dark:bg-blue-500/10 text-zinc-900 dark:text-white' : 'border-transparent text-zinc-500 dark:text-white/40 hover:border-zinc-300 dark:hover:border-white/15 hover:bg-zinc-50 dark:hover:bg-white/3 hover:text-zinc-700 dark:hover:text-white/75' }}">
                        <flux:icon.chart-bar
                            class="size-4 shrink-0 {{ $dashboardActive ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-400 dark:text-white/25 group-hover:text-zinc-500 dark:group-hover:text-white/50' }}" />
                        {{ __('Dashboard') }}
                    </a>

                    @canany(['roles.view', 'permissions.view', 'users.view'])
                    <div class="mt-4 mb-1 px-4 text-[10px] font-medium tracking-[0.12em] uppercase text-zinc-300 dark:text-white/20">
                        {{ __('Access Control') }}
                    </div>
                    @endcanany

                    @can('roles.view')
                    @php $rolesActive = request()->routeIs('backoffice.roles.*'); @endphp
                    <a href="{{ route('backoffice.roles.index') }}" wire:navigate
                        class="group flex items-center gap-3 border-s-2 px-4 py-2.5 text-sm transition-colors
                            {{ $rolesActive ? 'border-blue-400 bg-blue-50 dark:bg-blue-500/10 text-zinc-900 dark:text-white' : 'border-transparent text-zinc-500 dark:text-white/40 hover:border-zinc-300 dark:hover:border-white/15 hover:bg-zinc-50 dark:hover:bg-white/3 hover:text-zinc-700 dark:hover:text-white/75' }}">
                        <flux:icon.shield-check
                            class="size-4 shrink-0 {{ $rolesActive ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-400 dark:text-white/25 group-hover:text-zinc-500 dark:group-hover:text-white/50' }}" />
                        {{ __('Roles') }}
                    </a>
                    @endcan

                    @can('permissions.view')
                    @php $permissionsActive = request()->routeIs('backoffice.permissions.index'); @endphp
                    <a href="{{ route('backoffice.permissions.index') }}" wire:navigate
                        class="group flex items-center gap-3 border-s-2 px-4 py-2.5 text-sm transition-colors
                            {{ $permissionsActive ? 'border-blue-400 bg-blue-50 dark:bg-blue-500/10 text-zinc-900 dark:text-white' : 'border-transparent text-zinc-500 dark:text-white/40 hover:border-zinc-300 dark:hover:border-white/15 hover:bg-zinc-50 dark:hover:bg-white/3 hover:text-zinc-700 dark:hover:text-white/75' }}">
                        <flux:icon.key
                            class="size-4 shrink-0 {{ $permissionsActive ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-400 dark:text-white/25 group-hover:text-zinc-500 dark:group-hover:text-white/50' }}" />
                        {{ __('Permissions') }}
                    </a>
                    @endcan

                    @can('users.view')
                    @php $usersActive = request()->routeIs('backoffice.users.index'); @endphp
                    <a href="{{ route('backoffice.users.index') }}" wire:navigate
                        class="group flex items-center gap-3 border-s-2 px-4 py-2.5 text-sm transition-colors
                            {{ $usersActive ? 'border-blue-400 bg-blue-50 dark:bg-blue-500/10 text-zinc-900 dark:text-white' : 'border-transparent text-zinc-500 dark:text-white/40 hover:border-zinc-300 dark:hover:border-white/15 hover:bg-zinc-50 dark:hover:bg-white/3 hover:text-zinc-700 dark:hover:text-white/75' }}">
                        <flux:icon.users
                            class="size-4 shrink-0 {{ $usersActive ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-400 dark:text-white/25 group-hover:text-zinc-500 dark:group-hover:text-white/50' }}" />
                        {{ __('Users') }}
                    </a>
                    @endcan

                    {{-- Links contributed by feature modules (see Module::contribute
                        'backoffice.nav'). A disabled module contributes nothing, so
                        there's no per-module check to keep in sync here. --}}
                    @php
                    $moduleNav = collect(\App\Support\Modules\Module::contributions('backoffice.nav'))
                        ->filter(fn ($item) => Route::has($item['route'])
                            && (! isset($item['visible']) || $item['visible']())
                            && (! isset($item['permission']) || Gate::any((array) $item['permission'])))
                        ->sortBy(fn ($item) => $item['order'] ?? 100);
                    @endphp
                    @if ($moduleNav->isNotEmpty() || Gate::allows('languages.edit'))
                    <div class="mt-4 mb-1 px-4 text-[10px] font-medium tracking-[0.12em] uppercase text-zinc-300 dark:text-white/20">
                        {{ __('System') }}
                    </div>
                    @endif

                    @foreach ($moduleNav as $item)
                    @php $itemActive = request()->routeIs($item['active'] ?? $item['route']); @endphp
                    <a href="{{ route($item['route']) }}" wire:navigate
                        class="group flex items-center gap-3 border-s-2 px-4 py-2.5 text-sm transition-colors
                            {{ $itemActive ? 'border-blue-400 bg-blue-50 dark:bg-blue-500/10 text-zinc-900 dark:text-white' : 'border-transparent text-zinc-500 dark:text-white/40 hover:border-zinc-300 dark:hover:border-white/15 hover:bg-zinc-50 dark:hover:bg-white/3 hover:text-zinc-700 dark:hover:text-white/75' }}">
                        <flux:icon :name="$item['icon'] ?? 'squares-2x2'"
                            class="size-4 shrink-0 {{ $itemActive ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-400 dark:text-white/25 group-hover:text-zinc-500 dark:group-hover:text-white/50' }}" />
                        {{ __($item['label']) }}
                    </a>
                    @endforeach

                    @can('languages.edit')
                    @php $settingsActive = request()->routeIs('backoffice.settings.index'); @endphp
                    <a href="{{ route('backoffice.settings.index') }}" wire:navigate
                        class="group flex items-center gap-3 border-s-2 px-4 py-2.5 text-sm transition-colors
                            {{ $settingsActive ? 'border-blue-400 bg-blue-50 dark:bg-blue-500/10 text-zinc-900 dark:text-white' : 'border-transparent text-zinc-500 dark:text-white/40 hover:border-zinc-300 dark:hover:border-white/15 hover:bg-zinc-50 dark:hover:bg-white/3 hover:text-zinc-700 dark:hover:text-white/75' }}">
                        <flux:icon.cog-6-tooth
                            class="size-4 shrink-0 {{ $settingsActive ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-400 dark:text-white/25 group-hover:text-zinc-500 dark:group-hover:text-white/50' }}" />
                        {{ __('Settings') }}
                    </a>
                    @endcan


                </nav>

                <div class="shrink-0 border-t border-zinc-200 dark:border-white/6 pt-2">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="flex w-full items-center gap-3 border-s-2 border-transparent px-4 py-2.5 text-sm text-zinc-400 dark:text-white/30 transition-colors hover:border-red-500/40 hover:bg-red-500/5 hover:text-red-400">
                            <flux:icon.arrow-right-start-on-rectangle class="size-4 shrink-0 rtl:rotate-180" />
                            {{ __('Sign out') }}
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
