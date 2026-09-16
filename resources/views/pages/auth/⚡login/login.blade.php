@php $title = 'Sign In'; @endphp

<div class="flex min-h-screen">

    {{-- ── Brand panel ──────────────────────────────────────────────────── --}}
    <div
        class="relative hidden overflow-hidden border-e border-zinc-200 dark:border-white/6 bg-blue-950 lg:flex lg:w-[42%] lg:flex-col">

        {{-- Subtle grid overlay --}}
        <div class="absolute inset-0"
            style="background-image: linear-gradient(rgba(99,123,248,0.07) 1px, transparent 1px), linear-gradient(90deg, rgba(99,123,248,0.07) 1px, transparent 1px); background-size: 56px 56px;">
        </div>

        {{-- Top accent bar --}}
        <div class="absolute inset-x-0 top-0 h-0.5 bg-blue-400/60"></div>

        <div class="relative flex h-full flex-col justify-between p-10">

            {{-- Logo --}}
            <div class="flex items-center gap-3">
                <img src="{{ Vite::asset('resources/assets/images/logo.svg') }}" alt="{{ config('app.name') }}"
                    class="h-9 w-auto" />
                <span class="text-sm font-semibold text-white">{{ config('app.name') }}</span>
            </div>

            {{-- Tagline --}}
            <div>
                <p class="mb-3 text-[10px] tracking-[0.35em] uppercase text-blue-300/50">Laravel Multidomain Starter</p>
                <h2 class="text-5xl font-extrabold leading-[1.05] tracking-tighter text-white">
                    Ship apps<br>
                    <span class="text-blue-300">across</span><br>
                    every domain.
                </h2>
                <div class="mt-8 h-[3px] w-10 bg-blue-400"></div>
            </div>
        </div>
    </div>

    {{-- ── Form panel ────────────────────────────────────────────────────── --}}
    <div class="flex flex-1 flex-col bg-white dark:bg-zinc-950">

        {{-- Top bar --}}
        <div class="flex h-12 items-center justify-between border-b border-zinc-200 dark:border-white/6 px-6 lg:px-10">
            <div class="flex items-center gap-3 lg:hidden">
                <img src="{{ Vite::asset('resources/assets/images/logo.svg') }}" alt="{{ config('app.name') }}"
                    class="h-7 w-auto" />
                <span class="text-sm font-semibold text-zinc-900 dark:text-white">{{ config('app.name') }}</span>
            </div>
            <div class="hidden lg:block"></div>
            <div class="flex items-center gap-2">
                <div class="flex items-center gap-2 text-xs text-zinc-400 dark:text-white/35">
                    No account?
                    <flux:link href="{{ route('auth.register') }}" wire:navigate
                        class="font-medium! text-zinc-700! dark:text-white/70! hover:text-zinc-900! dark:hover:text-white!">
                        Create one
                    </flux:link>
                </div>
                <x-theme-switcher />
            </div>
        </div>

        {{-- Form area --}}
        <div class="flex flex-1 items-center justify-center px-6 py-10 lg:px-16">
            <div class="w-full max-w-sm">

                {{-- Heading block with left accent --}}
                <div class="mb-7 border-s-[3px] border-blue-500 ps-4">
                    <p class="mb-1 text-[10px] tracking-[0.3em] uppercase text-blue-500 dark:text-blue-400/55">Account
                        access</p>
                    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Welcome back</h1>
                </div>

                {{-- Deletion notice --}}
                @if (session('deletion_requested'))
                <div class="mb-5 border border-amber-500/20 bg-amber-500/6 p-4">
                    <div class="flex items-center gap-2">
                        <flux:icon.clock class="size-3.5 shrink-0 text-amber-400" />
                        <p class="text-xs font-medium text-amber-300">Account deletion scheduled</p>
                    </div>
                    <p class="mt-1.5 ps-5 text-xs leading-relaxed text-zinc-500 dark:text-white/45">
                        Your account will be permanently deleted in
                        <span class="text-zinc-700 dark:text-white/75">{{
                            \App\Models\AccountDeletionRequest::GRACE_PERIOD_DAYS }} days</span>.
                        To cancel, <span class="font-medium text-amber-300">sign back in</span>.
                    </p>
                </div>
                @endif

                {{-- Form (no card — directly on bg) --}}
                <form wire:submit="login" class="space-y-4">

                    <flux:field>
                        <flux:label>Email Address</flux:label>
                        <flux:input type="email" placeholder="you@example.com" wire:model="email"
                            autocomplete="email" />
                        <flux:error name="email" />
                    </flux:field>

                    <flux:field>
                        <div class="mb-1.5 flex items-center justify-between">
                            <flux:label>Password</flux:label>
                            <flux:link href="{{ route('auth.forgot-password') }}" wire:navigate class="text-xs!">
                                Forgot?
                            </flux:link>
                        </div>
                        <flux:input type="password" placeholder="••••••••" wire:model="password"
                            autocomplete="current-password" viewable />
                        <flux:error name="password" />
                    </flux:field>

                    <flux:checkbox label="Remember me for 30 days" wire:model="remember" class="text-sm!" />

                    <div class="pt-1">
                        <flux:button type="submit" variant="primary" class="w-full">Sign In</flux:button>
                    </div>

                </form>

            </div>
        </div>

    </div>

</div>