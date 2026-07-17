<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ $title ?? config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

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
                <span class="text-[11px] font-medium tracking-[0.12em] uppercase text-white/30">App</span>
            </div>

            @auth
            <div class="flex items-center gap-3">
                <flux:dropdown position="bottom" align="end">
                    <button type="button" class="flex items-center gap-2.5 rounded-lg py-1 pl-1 pr-2 transition-colors hover:bg-white/5">
                        <flux:avatar size="sm" name="{{ auth()->user()->name }}" />
                        <div class="hidden text-left sm:block">
                            <div class="text-sm leading-tight text-white/85">{{ auth()->user()->name }}</div>
                            <div class="text-xs leading-tight text-white/40">{{ auth()->user()->email }}</div>
                        </div>
                        <flux:icon.chevron-down class="hidden size-3.5 text-white/30 sm:block" />
                    </button>

                    <flux:menu class="min-w-56 dark:border-white/10! dark:bg-zinc-900! shadow-lg shadow-black/40">
                        <div class="px-3 py-2">
                            <div class="text-sm text-white/85">{{ auth()->user()->name }}</div>
                            <div class="text-xs text-white/40">{{ auth()->user()->email }}</div>
                        </div>
                        <flux:menu.separator class="dark:bg-white/10!" />
                        <flux:menu.item icon="user-circle" href="{{ route('account.index') }}" class="dark:data-active:bg-white/8!">
                            Account
                        </flux:menu.item>
                        @if (Route::has('account.security'))
                        <flux:menu.item icon="lock-closed" href="{{ route('account.security') }}" class="dark:data-active:bg-white/8!">
                            Security
                        </flux:menu.item>
                        @endif
                        <flux:menu.separator class="dark:bg-white/10!" />
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" variant="danger">
                                Logout
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </div>
            @endauth

        </div>
    </header>

    {{-- Mobile nav --}}
    <div class="border-b border-white/6 bg-zinc-950 lg:hidden sticky top-13.25 z-30">
        <div class="mx-auto max-w-7xl overflow-x-auto px-4">
            <nav class="flex">
                @php
                $mobileItems = [
                ['label' => 'Dashboard', 'route' => 'app.dashboard'],
                ['label' => 'Projects', 'route' => 'app.projects.index'],
                ['label' => 'Billing', 'route' => 'app.billing.index'],
                ['label' => 'Reports', 'route' => 'app.reports.index'],
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

                    @php $dashboardActive = request()->routeIs('app.dashboard'); @endphp
                    <a href="{{ route('app.dashboard') }}" wire:navigate
                        class="group flex items-center gap-3 border-l-2 px-4 py-2.5 text-sm transition-colors
                            {{ $dashboardActive ? 'border-blue-400 bg-blue-500/7 text-white' : 'border-transparent text-white/40 hover:border-white/15 hover:bg-white/3 hover:text-white/75' }}">
                        <flux:icon.chart-bar
                            class="size-4 shrink-0 {{ $dashboardActive ? 'text-blue-400' : 'text-white/25 group-hover:text-white/50' }}" />
                        Dashboard
                    </a>

                    <div class="mt-4 mb-1 px-4 text-[10px] font-medium tracking-[0.12em] uppercase text-white/20">
                        Workspace
                    </div>

                    @if (Route::has('app.projects.index'))
                    @php $projectsActive = request()->routeIs('app.projects.index'); @endphp
                    <a href="{{ route('app.projects.index') }}" wire:navigate
                        class="group flex items-center gap-3 border-l-2 px-4 py-2.5 text-sm transition-colors
                                {{ $projectsActive ? 'border-blue-400 bg-blue-500/7 text-white' : 'border-transparent text-white/40 hover:border-white/15 hover:bg-white/3 hover:text-white/75' }}">
                        <flux:icon.folder
                            class="size-4 shrink-0 {{ $projectsActive ? 'text-blue-400' : 'text-white/25 group-hover:text-white/50' }}" />
                        Projects
                    </a>
                    @else
                    <span
                        class="flex cursor-not-allowed items-center gap-3 border-l-2 border-transparent px-4 py-2.5 text-sm text-white/20">
                        <flux:icon.folder class="size-4 shrink-0 text-white/15" />
                        Projects
                        <span class="ml-auto text-[10px] text-white/20">Soon</span>
                    </span>
                    @endif

                    @if (Route::has('app.billing.index'))
                    @php $billingActive = request()->routeIs('app.billing.index'); @endphp
                    <a href="{{ route('app.billing.index') }}" wire:navigate
                        class="group flex items-center gap-3 border-l-2 px-4 py-2.5 text-sm transition-colors
                                {{ $billingActive ? 'border-blue-400 bg-blue-500/7 text-white' : 'border-transparent text-white/40 hover:border-white/15 hover:bg-white/3 hover:text-white/75' }}">
                        <flux:icon.credit-card
                            class="size-4 shrink-0 {{ $billingActive ? 'text-blue-400' : 'text-white/25 group-hover:text-white/50' }}" />
                        Billing
                    </a>
                    @else
                    <span
                        class="flex cursor-not-allowed items-center gap-3 border-l-2 border-transparent px-4 py-2.5 text-sm text-white/20">
                        <flux:icon.credit-card class="size-4 shrink-0 text-white/15" />
                        Billing
                        <span class="ml-auto text-[10px] text-white/20">Soon</span>
                    </span>
                    @endif

                    @if (Route::has('app.reports.index'))
                    @php $reportsActive = request()->routeIs('app.reports.index'); @endphp
                    <a href="{{ route('app.reports.index') }}" wire:navigate
                        class="group flex items-center gap-3 border-l-2 px-4 py-2.5 text-sm transition-colors
                                {{ $reportsActive ? 'border-blue-400 bg-blue-500/7 text-white' : 'border-transparent text-white/40 hover:border-white/15 hover:bg-white/3 hover:text-white/75' }}">
                        <flux:icon.document-chart-bar
                            class="size-4 shrink-0 {{ $reportsActive ? 'text-blue-400' : 'text-white/25 group-hover:text-white/50' }}" />
                        Reports
                    </a>
                    @else
                    <span
                        class="flex cursor-not-allowed items-center gap-3 border-l-2 border-transparent px-4 py-2.5 text-sm text-white/20">
                        <flux:icon.document-chart-bar class="size-4 shrink-0 text-white/15" />
                        Reports
                        <span class="ml-auto text-[10px] text-white/20">Soon</span>
                    </span>
                    @endif

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
