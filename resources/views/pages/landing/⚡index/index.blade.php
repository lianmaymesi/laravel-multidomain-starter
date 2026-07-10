@php $title = 'Welcome'; @endphp

<div class="mx-auto flex min-h-screen max-w-6xl flex-col justify-center px-6 py-16 sm:px-8">

    {{-- Hero --}}
    <div class="mx-auto max-w-2xl text-center">
        <div class="mx-auto flex w-fit items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs text-white/50">
            <flux:icon.globe-alt class="size-3.5 text-blue-400" />
            {{ config('multidomain.sub_domains.app') }} · {{ config('multidomain.sub_domains.backoffice') }} · {{ config('multidomain.sub_domains.account') }}
        </div>

        <flux:heading size="xl" class="mt-6 text-4xl font-semibold sm:text-5xl">
            {{ config('app.name') }}
        </flux:heading>

        <flux:text class="mx-auto mt-4 max-w-xl text-white/50">
            A Laravel starter kit that splits your application across dedicated subdomains — app, backoffice,
            accounts and auth — each with its own layout, assets and middleware, wired together from one codebase.
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

        <flux:card class="space-y-2">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-500/15">
                <flux:icon.globe-alt class="size-4.5 text-blue-400" />
            </div>
            <flux:heading size="sm">Domain-based routing</flux:heading>
            <flux:text class="text-sm text-white/50">Each portal is registered in <code class="text-white/70">config/multidomain.php</code> and routed with <code class="text-white/70">Route::domain()</code> — no shared middleware or namespace bleed.</flux:text>
        </flux:card>

        <flux:card class="space-y-2">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-500/15">
                <flux:icon.command-line class="size-4.5 text-blue-400" />
            </div>
            <flux:heading size="sm">Scaffold subdomains instantly</flux:heading>
            <flux:text class="text-sm text-white/50"><code class="text-white/70">php artisan make:subdomain</code> generates the route file, layout, Vite assets and a default dashboard for a brand-new portal in seconds.</flux:text>
        </flux:card>

        <flux:card class="space-y-2">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-500/15">
                <flux:icon.bolt class="size-4.5 text-blue-400" />
            </div>
            <flux:heading size="sm">Livewire 4 components</flux:heading>
            <flux:text class="text-sm text-white/50">Pages are built as single-file Livewire components, giving every portal reactive, server-driven UI without a separate API layer.</flux:text>
        </flux:card>

        <flux:card class="space-y-2">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-500/15">
                <flux:icon.swatch class="size-4.5 text-blue-400" />
            </div>
            <flux:heading size="sm">Flux UI design system</flux:heading>
            <flux:text class="text-sm text-white/50">Every portal shares the same accessible component kit and theme tokens, so new subdomains look consistent from the first commit.</flux:text>
        </flux:card>

    </div>

</div>
