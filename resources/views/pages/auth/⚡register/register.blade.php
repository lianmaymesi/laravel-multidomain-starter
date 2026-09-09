<div class="flex min-h-screen">

    {{-- ── Brand panel ──────────────────────────────────────────────────── --}}
    <div
        class="relative hidden overflow-hidden border-r border-zinc-200 dark:border-white/6 bg-blue-950 lg:flex lg:w-[38%] lg:flex-col">

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
                <p class="mb-3 text-[10px] tracking-[0.35em] uppercase text-blue-300/50">Start building today</p>
                <h2 class="text-5xl font-extrabold leading-[1.05] tracking-tighter text-white">
                    Spin up<br>
                    <span class="text-blue-300">your next</span><br>
                    subdomain.
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
                    Have account?
                    <flux:link href="{{ route('auth.login') }}" wire:navigate
                        class="font-medium! text-zinc-700! dark:text-white/70! hover:text-zinc-900! dark:hover:text-white!">
                        Sign in
                    </flux:link>
                </div>
                <x-theme-switcher />
            </div>
        </div>

        {{-- Form area --}}
        <div class="flex flex-1 items-center justify-center px-6 py-10 lg:px-16">
            <div class="w-full max-w-md">

                {{-- Heading block with left accent --}}
                <div class="mb-7 border-l-[3px] border-blue-500 pl-4">
                    <p class="mb-1 text-[10px] tracking-[0.3em] uppercase text-blue-500 dark:text-blue-400/55">New
                        account</p>
                    <h1 class="text-3xl font-bold tracking-tight text-zinc-900 dark:text-white">Create account</h1>
                </div>

                {{-- Form (no card — directly on bg) --}}
                <form wire:submit="register" class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                    <div class="sm:col-span-2">
                        <flux:field>
                            <flux:label>Full Name</flux:label>
                            <flux:input type="text" placeholder="Peter Nelson" wire:model="name" />
                            <flux:error name="name" />
                        </flux:field>
                    </div>

                    @if (count(config('multidomain.registerable_portals', [])) > 0)
                    <div class="sm:col-span-2">
                        <flux:field>
                            <flux:label>Select the user type</flux:label>
                            <flux:select wire:model="userType" placeholder="Standard account">
                                <flux:select.option value="">Standard account</flux:select.option>
                                @foreach (config('multidomain.registerable_portals', []) as $key => $label)
                                <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select>
                            <flux:error name="userType" />
                        </flux:field>
                    </div>
                    @endif

                    @if (config('multidomain.phone_verification_enabled'))
                    <div class="sm:col-span-2">
                        <div class="flex gap-2">
                            @if (config('multidomain.phone_country_mode') === 'multi')
                            <div class="w-24 shrink-0">
                                <flux:field>
                                    <flux:label>Code</flux:label>
                                    <flux:input placeholder="+91" wire:model="country_code" class="text-center" />
                                </flux:field>
                            </div>
                            @endif
                            <div class="flex-1">
                                <flux:field>
                                    <flux:label>Phone Number</flux:label>
                                    <flux:input mask="99999-99999" placeholder="98765-43210" wire:model="phone" />
                                </flux:field>
                            </div>
                        </div>
                        <flux:error name="country_code" />
                        <flux:error name="phone" />
                    </div>
                    @endif

                    <div class="sm:col-span-2">
                        <flux:field>
                            <flux:label>Email Address</flux:label>
                            <flux:input type="email" placeholder="you@example.com" wire:model="email" />
                            <flux:error name="email" />
                        </flux:field>
                    </div>

                    <div class="sm:col-span-2">
                        <flux:field>
                            <flux:label>Password</flux:label>
                            <flux:input type="password" placeholder="Choose a strong password" wire:model="password"
                                viewable />
                            <flux:error name="password" />
                        </flux:field>
                    </div>

                    <div class="sm:col-span-2">
                        <flux:field>
                            <flux:label>Confirm Password</flux:label>
                            <flux:input type="password" placeholder="Re-enter your password"
                                wire:model="password_confirmation" viewable />
                            <flux:error name="password_confirmation" />
                        </flux:field>
                    </div>

                    <div class="pt-1 sm:col-span-2">
                        <flux:button type="submit" variant="primary" class="w-full">Create Account</flux:button>
                    </div>

                </form>

            </div>
        </div>

    </div>

</div>