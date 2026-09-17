<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app(App\Services\LanguageService::class)->currentDirection() }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ($title ?? __('Account')) . ' · ' . config('app.name') }}</title>
    @vite(['resources/css/account.css', 'resources/js/app.js'])
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
                <span class="text-[11px] font-medium tracking-[0.12em] uppercase text-zinc-400 dark:text-white/30">{{ __('Account') }}</span>
            </div>

            <div class="flex items-center gap-3">
                <x-theme-switcher />
                @auth
                <a href="{{ auth()->user()->redirect() }}"
                    class="hidden items-center gap-2 border border-zinc-200 dark:border-white/10 px-3 py-1.5 text-xs text-zinc-500 dark:text-white/40 transition-colors hover:bg-zinc-100 dark:hover:bg-white/5 hover:text-zinc-700 dark:hover:text-white/70 sm:flex">
                    <flux:icon.arrow-left class="size-3 rtl:rotate-180" />
                    {{ __('Back to app') }}
                </a>
                <flux:avatar size="sm" name="{{ auth()->user()->name }}" />
                @endauth
            </div>

        </div>
    </header>

    {{-- Mobile nav --}}
    <div class="border-b border-zinc-200 dark:border-white/6 bg-white dark:bg-zinc-950 lg:hidden sticky top-13.25 z-30">
        <div class="mx-auto max-w-7xl overflow-x-auto px-4">
            <nav class="flex">
                @php
                $mobileItems = [
                ['label' => __('Profile'), 'route' => 'account.index'],
                ['label' => __('Security'), 'route' => 'account.security'],
                ['label' => __('2FA'), 'route' => 'account.two-factor-setup'],
                ['label' => __('Export'), 'route' => 'account.export'],
                ['label' => __('Settings'), 'route' => 'account.settings'],
                ['label' => __('Settings'), 'route' => 'account.settings'],
                ];
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

                    @php $profileActive = request()->routeIs('account.index'); @endphp
                    <a href="{{ route('account.index') }}" wire:navigate
                        class="group flex items-center gap-3 border-s-2 px-4 py-2.5 text-sm transition-colors
                            {{ $profileActive ? 'border-blue-400 bg-blue-50 dark:bg-blue-500/10 text-zinc-900 dark:text-white' : 'border-transparent text-zinc-500 dark:text-white/40 hover:border-zinc-300 dark:hover:border-white/15 hover:bg-zinc-50 dark:hover:bg-white/3 hover:text-zinc-700 dark:hover:text-white/75' }}">
                        <flux:icon.user
                            class="size-4 shrink-0 {{ $profileActive ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-400 dark:text-white/25 group-hover:text-zinc-500 dark:group-hover:text-white/50' }}" />
                        {{ __('Profile') }}
                    </a>

                    @if (Route::has('account.security'))
                    @php $secActive = request()->routeIs('account.security'); @endphp
                    <a href="{{ route('account.security') }}" wire:navigate
                        class="group flex items-center gap-3 border-s-2 px-4 py-2.5 text-sm transition-colors
                                {{ $secActive ? 'border-blue-400 bg-blue-50 dark:bg-blue-500/10 text-zinc-900 dark:text-white' : 'border-transparent text-zinc-500 dark:text-white/40 hover:border-zinc-300 dark:hover:border-white/15 hover:bg-zinc-50 dark:hover:bg-white/3 hover:text-zinc-700 dark:hover:text-white/75' }}">
                        <flux:icon.lock-closed
                            class="size-4 shrink-0 {{ $secActive ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-400 dark:text-white/25 group-hover:text-zinc-500 dark:group-hover:text-white/50' }}" />
                        {{ __('Security') }}
                    </a>
                    @else
                    <span
                        class="flex cursor-not-allowed items-center gap-3 border-s-2 border-transparent px-4 py-2.5 text-sm text-zinc-300 dark:text-white/20">
                        <flux:icon.lock-closed class="size-4 shrink-0 text-zinc-300 dark:text-white/15" />
                        {{ __('Security') }}
                        <span class="ms-auto text-[10px] text-zinc-300 dark:text-white/20">{{ __('Soon') }}</span>
                    </span>
                    @endif

                    @php $tfaActive = request()->routeIs('account.two-factor-setup'); @endphp
                    <a href="{{ route('account.two-factor-setup') }}" wire:navigate
                        class="group flex items-center gap-3 border-s-2 px-4 py-2.5 text-sm transition-colors
                            {{ $tfaActive ? 'border-blue-400 bg-blue-50 dark:bg-blue-500/10 text-zinc-900 dark:text-white' : 'border-transparent text-zinc-500 dark:text-white/40 hover:border-zinc-300 dark:hover:border-white/15 hover:bg-zinc-50 dark:hover:bg-white/3 hover:text-zinc-700 dark:hover:text-white/75' }}">
                        <flux:icon.shield-check
                            class="size-4 shrink-0 {{ $tfaActive ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-400 dark:text-white/25 group-hover:text-zinc-500 dark:group-hover:text-white/50' }}" />
                        {{ __('Two-Factor') }}
                    </a>

                    @if (Route::has('account.export'))
                    @php $exportActive = request()->routeIs('account.export'); @endphp
                    <a href="{{ route('account.export') }}" wire:navigate
                        class="group flex items-center gap-3 border-s-2 px-4 py-2.5 text-sm transition-colors
                                {{ $exportActive ? 'border-blue-400 bg-blue-50 dark:bg-blue-500/10 text-zinc-900 dark:text-white' : 'border-transparent text-zinc-500 dark:text-white/40 hover:border-zinc-300 dark:hover:border-white/15 hover:bg-zinc-50 dark:hover:bg-white/3 hover:text-zinc-700 dark:hover:text-white/75' }}">
                        <flux:icon.arrow-down-tray
                            class="size-4 shrink-0 {{ $exportActive ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-400 dark:text-white/25 group-hover:text-zinc-500 dark:group-hover:text-white/50' }}" />
                        {{ __('Export Data') }}
                    </a>
                    @endif

                    @if (Route::has('account.settings'))
                    @php $settActive = request()->routeIs('account.settings'); @endphp
                    <a href="{{ route('account.settings') }}" wire:navigate
                        class="group flex items-center gap-3 border-s-2 px-4 py-2.5 text-sm transition-colors
                                {{ $settActive ? 'border-blue-400 bg-blue-50 dark:bg-blue-500/10 text-zinc-900 dark:text-white' : 'border-transparent text-zinc-500 dark:text-white/40 hover:border-zinc-300 dark:hover:border-white/15 hover:bg-zinc-50 dark:hover:bg-white/3 hover:text-zinc-700 dark:hover:text-white/75' }}">
                        <flux:icon.cog-6-tooth
                            class="size-4 shrink-0 {{ $settActive ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-400 dark:text-white/25 group-hover:text-zinc-500 dark:group-hover:text-white/50' }}" />
                        {{ __('Settings') }}
                    </a>
                    @else
                    <span
                        class="flex cursor-not-allowed items-center gap-3 border-s-2 border-transparent px-4 py-2.5 text-sm text-zinc-300 dark:text-white/20">
                        <flux:icon.cog-6-tooth class="size-4 shrink-0 text-zinc-300 dark:text-white/15" />
                        {{ __('Settings') }}
                        <span class="ms-auto text-[10px] text-zinc-300 dark:text-white/20">{{ __('Soon') }}</span>
                    </span>
                    @endif

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