<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ($title ?? 'Account') . ' · ' . config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>

<body class="min-h-screen bg-zinc-950 text-white antialiased">

    {{-- Sticky header --}}
    <header class="sticky top-0 z-40 border-b border-white/[0.07] bg-zinc-950/90 backdrop-blur-md">
        <div class="mx-auto flex h-14 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">

            <div class="flex items-center gap-3">
                <img src="{{ Vite::asset('resources/assets/images/logo.svg') }}" alt="{{ config('app.name') }}"
                    class="h-7 w-auto" />
                <div class="h-4 w-px bg-white/15 hidden sm:block"></div>
                <span class="hidden text-sm font-medium text-white/50 sm:block">Account</span>
            </div>

            <div class="flex items-center gap-4">
                @auth
                <a href="{{ auth()->user()->redirect() }}"
                    class="hidden items-center gap-1.5 rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs text-white/50 transition-colors hover:bg-white/10 hover:text-white/80 sm:flex">
                    <flux:icon.arrow-left class="size-3" />
                    Back to app
                </a>
                <div class="flex items-center gap-2.5">
                    <flux:avatar size="sm" name="{{ auth()->user()->name }}" />
                </div>
                @endauth
            </div>
        </div>
    </header>

    {{-- Mobile horizontal nav --}}
    <div class="border-b border-white/[0.07] bg-zinc-950 lg:hidden">
        <div class="mx-auto max-w-7xl overflow-x-auto px-4">
            <nav class="flex gap-1 py-2">
                @php
                $mobileItems = [
                ['label' => 'Profile', 'route' => 'account.index'],
                ['label' => 'Security', 'route' => 'account.security'],
                ['label' => '2FA', 'route' => 'account.two-factor-setup'],
                ['label' => 'Export', 'route' => 'account.export'],
                ['label' => 'Settings', 'route' => 'account.settings'],
                ];
                @endphp
                @foreach ($mobileItems as $item)
                @php $active = request()->routeIs($item['route']); @endphp
                @if (Route::has($item['route']))
                <a href="{{ route($item['route']) }}"
                    class="shrink-0 rounded-lg px-3 py-1.5 text-sm transition-colors
                                {{ $active ? 'bg-white/10 font-medium text-white' : 'text-white/50 hover:bg-white/5 hover:text-white/80' }}">
                    {{ $item['label'] }}
                </a>
                @else
                <span class="shrink-0 cursor-not-allowed rounded-lg px-3 py-1.5 text-sm text-white/25">
                    {{ $item['label'] }}
                </span>
                @endif
                @endforeach
            </nav>
        </div>
    </div>

    {{-- Page body --}}
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="flex gap-10">

            {{-- Sidebar --}}
            <aside class="hidden w-52 shrink-0 lg:flex lg:flex-col">
                <nav class="space-y-0.5">

                    @php
                    $profileActive = request()->routeIs('account.index');
                    @endphp
                    <a href="{{ route('account.index') }}" wire:navigate
                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors
                            {{ $profileActive ? 'bg-white/10 font-medium text-white' : 'text-white/50 hover:bg-white/5 hover:text-white/80' }}">
                        <flux:icon.user class="size-4 {{ $profileActive ? 'text-white' : 'text-white/40' }}" />
                        Profile
                    </a>

                    @if (Route::has('account.security'))
                    @php $secActive = request()->routeIs('account.security'); @endphp
                    <a href="{{ route('account.security') }}" wire:navigate
                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors
                                {{ $secActive ? 'bg-white/10 font-medium text-white' : 'text-white/50 hover:bg-white/5 hover:text-white/80' }}">
                        <flux:icon.lock-closed class="size-4 {{ $secActive ? 'text-white' : 'text-white/40' }}" />
                        Security
                    </a>
                    @else
                    <span
                        class="flex cursor-not-allowed items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-white/25">
                        <flux:icon.lock-closed class="size-4 text-white/20" />
                        Security
                        <span
                            class="ml-auto rounded-md border border-white/[0.06] px-1.5 py-0.5 text-[10px] text-white/20">Soon</span>
                    </span>
                    @endif

                    @php $tfaActive = request()->routeIs('account.two-factor-setup'); @endphp
                    <a href="{{ route('account.two-factor-setup') }}" wire:navigate
                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors
                            {{ $tfaActive ? 'bg-white/10 font-medium text-white' : 'text-white/50 hover:bg-white/5 hover:text-white/80' }}">
                        <flux:icon.shield-check class="size-4 {{ $tfaActive ? 'text-white' : 'text-white/40' }}" />
                        Two-Factor Auth
                    </a>

                    @if (Route::has('account.export'))
                    @php $expActive = request()->routeIs('account.export'); @endphp
                    <a href="{{ route('account.export') }}" wire:navigate
                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors
                                {{ $expActive ? 'bg-white/10 font-medium text-white' : 'text-white/50 hover:bg-white/5 hover:text-white/80' }}">
                        <flux:icon.arrow-down-tray class="size-4 {{ $expActive ? 'text-white' : 'text-white/40' }}" />
                        Export Data
                    </a>
                    @endif

                    @if (Route::has('account.settings'))
                    @php $setActive = request()->routeIs('account.settings'); @endphp
                    <a href="{{ route('account.settings') }}"
                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors
                                {{ $setActive ? 'bg-white/10 font-medium text-white' : 'text-white/50 hover:bg-white/5 hover:text-white/80' }}">
                        <flux:icon.cog-6-tooth class="size-4 {{ $setActive ? 'text-white' : 'text-white/40' }}" />
                        Settings
                    </a>
                    @else
                    <span
                        class="flex cursor-not-allowed items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-white/25">
                        <flux:icon.cog-6-tooth class="size-4 text-white/20" />
                        Settings
                        <span
                            class="ml-auto rounded-md border border-white/6 px-1.5 py-0.5 text-[10px] text-white/20">Soon</span>
                    </span>
                    @endif

                </nav>

                <div class="mt-6 border-t border-white/[0.07] pt-5">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                            class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-white/35 transition-colors hover:bg-red-500/5 hover:text-red-400">
                            <flux:icon.log-out class="size-4" />
                            Sign out
                        </button>
                    </form>
                </div>
            </aside>

            {{-- Main content --}}
            <main class="min-w-0 flex-1">
                {{ $slot }}
            </main>

        </div>
    </div>

    @livewireScripts
    @fluxScripts
</body>

</html>
