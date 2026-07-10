@php $title = 'One Codebase, Every Domain'; @endphp

<div class="mx-auto flex min-h-screen max-w-6xl flex-col justify-center px-6 py-16 sm:px-8">

    {{-- Hero --}}
    <div class="mx-auto max-w-2xl text-center">
        <div class="mx-auto flex w-fit items-center gap-2">
            <a href="https://github.com/lianmaymesi/laravel-multidomain-starter" target="_blank" rel="noopener"
                class="flex items-center gap-1.5 border border-white/10 bg-white/5 px-3 py-1 text-xs text-white/60 transition-colors hover:border-white/20 hover:bg-white/10 hover:text-white">
                <svg class="size-3.5" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                    <path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8Z" />
                </svg>
                View on GitHub
            </a>
            <a href="https://github.com/lianmaymesi/laravel-multidomain-starter#readme" target="_blank" rel="noopener"
                class="flex items-center gap-1.5 border border-white/10 bg-white/5 px-3 py-1 text-xs text-white/60 transition-colors hover:border-white/20 hover:bg-white/10 hover:text-white">
                <flux:icon.book-open class="size-3.5" />
                Documentation
            </a>
        </div>

        <h1 class="mt-6 text-4xl leading-tight font-semibold tracking-tight text-white sm:text-5xl">
            One codebase.<br>
            <span class="text-blue-400">Every</span> domain.
        </h1>

        <flux:text class="mx-auto mt-4 max-w-xl text-white/50">
            Laravel Multidomain Starter splits your application across dedicated subdomains — app, backoffice,
            account and auth — each with its own layout, assets and middleware, wired together from one codebase.
        </flux:text>

        <div class="mt-8 flex items-center justify-center gap-3">
            @if (Route::has('login'))
            <flux:button variant="primary" :href="route('login')">Sign in</flux:button>
            @endif
            @if (Route::has('register'))
            <flux:button variant="ghost" :href="route('register')">Create account</flux:button>
            @endif
        </div>
    </div>

    {{-- Feature grid --}}
    <div class="mx-auto mt-16 grid w-full max-w-4xl grid-cols-1 gap-4 sm:grid-cols-2">

        <flux:card as="a" href="https://github.com/lianmaymesi/laravel-multidomain-starter/blob/main/config/multidomain.php"
            target="_blank" rel="noopener" class="space-y-2">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-500/15">
                <flux:icon.globe-alt class="size-4.5 text-blue-400" />
            </div>
            <flux:heading size="sm">Domain-based routing</flux:heading>
            <flux:text class="text-sm text-white/50">Each portal is registered in <code class="text-white/70">config/multidomain.php</code> and routed with <code class="text-white/70">Route::domain()</code> — no shared middleware, no namespace bleed.</flux:text>
            <flux:text class="text-sm text-blue-400">View config →</flux:text>
        </flux:card>

        <flux:card as="a" href="https://github.com/lianmaymesi/laravel-multidomain-starter/blob/main/app/Console/Commands/MakeSubdomainCommand.php"
            target="_blank" rel="noopener" class="space-y-2">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-500/15">
                <flux:icon.command-line class="size-4.5 text-blue-400" />
            </div>
            <flux:heading size="sm">Scaffold a portal in seconds</flux:heading>
            <flux:text class="text-sm text-white/50"><code class="text-white/70">php artisan make:subdomain</code> generates the route file, layout, Vite assets and a starter dashboard for a brand-new portal instantly.</flux:text>
            <flux:text class="text-sm text-blue-400">View command →</flux:text>
        </flux:card>

        <flux:card as="a" href="https://livewire.laravel.com" target="_blank" rel="noopener" class="space-y-2">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-500/15">
                <flux:icon.bolt class="size-4.5 text-blue-400" />
            </div>
            <flux:heading size="sm">Livewire 4, everywhere</flux:heading>
            <flux:text class="text-sm text-white/50">Pages are single-file Livewire components — reactive, server-driven UI in every portal, no separate API layer required.</flux:text>
            <flux:text class="text-sm text-blue-400">Livewire docs →</flux:text>
        </flux:card>

        <flux:card as="a" href="https://fluxui.dev" target="_blank" rel="noopener" class="space-y-2">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-500/15">
                <flux:icon.swatch class="size-4.5 text-blue-400" />
            </div>
            <flux:heading size="sm">One design system</flux:heading>
            <flux:text class="text-sm text-white/50">Every portal shares the same Flux UI components and theme tokens, so new subdomains look consistent from the first commit.</flux:text>
            <flux:text class="text-sm text-blue-400">Flux UI docs →</flux:text>
        </flux:card>

    </div>

</div>
