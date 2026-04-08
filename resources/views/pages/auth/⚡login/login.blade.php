@php $title = 'Sign In'; @endphp

<div class="min-h-screen bg-zinc-950 px-4 py-4 sm:px-6 lg:px-8">
    <div
        class="mx-auto grid min-h-[calc(100vh-2rem)] max-w-7xl overflow-hidden rounded-4xl border border-white/10 bg-zinc-950 shadow-2xl shadow-black/30 lg:grid-cols-[minmax(0,1fr)_minmax(420px,0.92fr)]">

        <section class="relative flex items-center overflow-hidden bg-zinc-950 px-6 py-8 sm:px-10 lg:px-12 lg:py-10">
            <div class="absolute inset-0">
                <div
                    class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(59,130,246,0.18),transparent_35%),radial-gradient(circle_at_20%_80%,rgba(16,185,129,0.12),transparent_28%)]">
                </div>
            </div>

            <div class="relative mx-auto w-full max-w-xl space-y-6">

                {{-- Header --}}
                <div class="inline-flex items-center gap-3 text-white transition hover:text-white/90">
                    <span
                        class="flex h-14 w-14 items-center justify-center rounded-2xl border border-white/10 bg-white/5 backdrop-blur-sm">
                        <img src="{{ Vite::asset('resources/assets/images/logo.svg') }}" alt="{{ config('app.name') }}"
                            class="h-10 w-auto" />
                    </span>
                    <div>
                        <flux:heading size="xl" class="text-3xl! font-semibold tracking-tight! text-white sm:text-4xl!">
                            Welcome back
                        </flux:heading>
                        <flux:text class="hidden md:block text-sm leading-7 text-white/65">
                            Sign in to continue to {{ config('app.name') }}
                        </flux:text>
                    </div>
                </div>

                {{-- Form Card --}}
                <form wire:submit="login"
                    class="space-y-4 rounded-[1.75rem] border border-white/10 bg-white/5 p-6 shadow-2xl shadow-black/20 backdrop-blur-md">

                    <flux:field>
                        <flux:label class="text-white/80">Email Address</flux:label>
                        <flux:input type="email" placeholder="you@example.com" wire:model="email"
                            autocomplete="email" />
                        <flux:error name="email" />
                    </flux:field>

                    <flux:field>
                        <div class="flex items-center justify-between mb-1">
                            <flux:label class="text-white/80">Password</flux:label>
                            <flux:link href="{{ route('auth.forgot-password') }}" wire:navigate
                                class="text-xs! text-white/50! hover:text-white/80! transition-colors">
                                Forgot password?
                            </flux:link>
                        </div>
                        <flux:input type="password" placeholder="Enter your password" wire:model="password"
                            autocomplete="current-password" viewable />
                        <flux:error name="password" />
                    </flux:field>

                    <flux:checkbox label="Remember me for 30 days"
                        class="text-sm! text-white/55! font-normal! cursor-pointer" />

                    <flux:button type="submit" variant="primary" class="w-full rounded-3xl! py-3.5!">
                        <span wire:loading.remove>Sign in</span>
                        <span wire:loading>Signing in…</span>
                    </flux:button>
                </form>

                <flux:subheading class="text-center text-white/60">
                    Don't have an account?
                    <flux:link href="{{ route('auth.register') }}" class="text-white" wire:navigate>
                        Create
                    </flux:link>
                </flux:subheading>
            </div>
        </section>

        <section class="relative overflow-hidden bg-zinc-900 px-6 py-8 text-white sm:px-10 lg:px-12 lg:py-10">
            <div class="absolute inset-0">
                <div
                    class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(59,130,246,0.45),transparent_38%),radial-gradient(circle_at_80%_20%,rgba(16,185,129,0.28),transparent_25%),linear-gradient(180deg,rgba(9,9,11,0.05),rgba(9,9,11,0.92))]">
                </div>
                <div class="absolute inset-0 bg-cover bg-center opacity-25"
                    style="background-image: url('/img/demo/auth_aurora_2x.png');"></div>
            </div>

            <div class="relative flex h-full items-end">
                <div class="mx-auto max-w-md space-y-8">
                    <div class="rounded-[1.75rem] border border-white/10 bg-white/3 p-5 backdrop-blur-lg">
                        <div class="flex gap-2 text-amber-300">
                            <flux:icon.star variant="solid" class="size-5" />
                            <flux:icon.star variant="solid" class="size-5" />
                            <flux:icon.star variant="solid" class="size-5" />
                            <flux:icon.star variant="solid" class="size-5" />
                            <flux:icon.star variant="solid" class="size-5" />
                        </div>

                        <p class="mt-4 text-xl leading-8 text-white/90">
                            "The registration experience now feels intentional, calm, and much easier to complete on the
                            first try."
                        </p>

                        <div class="mt-6 flex items-center gap-4">
                            <flux:avatar src="https://fluxui.dev/img/demo/caleb.png" size="xl" />
                            <div>
                                <div class="font-semibold text-white">Caleb Porzio</div>
                                <div class="text-sm text-white/65">Creator of Livewire</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

    </div>
</div>