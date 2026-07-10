@php $title = 'Forgot Password'; @endphp

<div class="flex min-h-screen flex-col bg-zinc-950">

    {{-- Top bar --}}
    <div class="flex h-12 shrink-0 items-center border-b border-white/6 px-6">
        <div class="flex items-center gap-3">
            <img src="{{ Vite::asset('resources/assets/images/logo.svg') }}" alt="{{ config('app.name') }}"
                class="h-7 w-auto" />
            <span class="text-xs font-semibold tracking-tight text-white/50">{{ config('app.name') }}</span>
        </div>
    </div>

    {{-- Content --}}
    <div class="flex flex-1 items-center justify-center px-4 py-12">
        <div class="w-full max-w-sm">

            {{-- Heading block --}}
            <div class="mb-6 border-l-[3px] border-blue-500 pl-4">
                <p class="mb-1 text-[10px] tracking-[0.25em] uppercase text-blue-400/55">Password recovery</p>
                <h1 class="text-2xl font-bold text-white">Reset password</h1>
                <p class="mt-1 text-sm text-white/40">
                    @if (! $otpSent)
                        Verify your identity to continue
                    @else
                        Enter the code we sent you
                    @endif
                </p>
            </div>

            {{-- Flash status --}}
            @if (session('status'))
                <div class="mb-4 border border-emerald-500/20 bg-emerald-500/6 px-4 py-3 text-center text-sm text-emerald-400">
                    {{ session('status') }}
                </div>
            @endif

            <flux:card class="p-5! space-y-5">

                {{-- ── Step 1: Choose method ── --}}
                @if (! $otpSent)

                    {{-- Sharp tab switcher --}}
                    @if (config('multidomain.phone_verification_enabled'))
                    <div class="flex border border-white/10 bg-white/3">
                        <button type="button" wire:click="switchTab('phone')"
                            @class([
                                'flex-1 py-2 text-sm font-medium transition-all',
                                'bg-white/8 text-white' => $activeTab === 'phone',
                                'text-white/40 hover:text-white/65' => $activeTab !== 'phone',
                            ])>
                            Phone
                        </button>
                        <button type="button" wire:click="switchTab('email')"
                            @class([
                                'flex-1 py-2 text-sm font-medium transition-all',
                                'bg-white/8 text-white' => $activeTab === 'email',
                                'text-white/40 hover:text-white/65' => $activeTab !== 'email',
                            ])>
                            Email
                        </button>
                    </div>
                    @endif

                    <form wire:submit="sendOtp" class="space-y-4">
                        @if ($activeTab === 'phone')
                            <div class="flex gap-2">
                                @if (config('multidomain.phone_country_mode') === 'multi')
                                <div class="w-24 shrink-0">
                                    <flux:field>
                                        <flux:label>Code</flux:label>
                                        <flux:input wire:model="country_code" placeholder="+91" class="text-center" />
                                    </flux:field>
                                </div>
                                @endif
                                <div class="flex-1">
                                    <flux:field>
                                        <flux:label>Phone Number</flux:label>
                                        <flux:input wire:model="phone" placeholder="98765-43210" mask="99999-99999" />
                                    </flux:field>
                                </div>
                            </div>
                            @if (config('multidomain.phone_country_mode') === 'multi')
                            <flux:error name="country_code" />
                            @endif
                            <flux:error name="phone" />
                        @else
                            <flux:field>
                                <flux:label>Email Address</flux:label>
                                <flux:input wire:model="email" type="email" placeholder="you@example.com"
                                    autocomplete="email" />
                                <flux:error name="email" />
                            </flux:field>
                        @endif
                        <flux:button type="submit" variant="primary" class="w-full">Send Code</flux:button>
                    </form>

                {{-- ── Step 2: Verify OTP ── --}}
                @else

                    <div class="flex items-center justify-between border border-white/10 bg-white/3 px-4 py-3">
                        <div>
                            <p class="mb-0.5 text-xs text-white/35">Code sent to</p>
                            <p class="text-sm font-medium text-white/85">
                                @if ($activeTab === 'phone')
                                    {{ $country_code }}
                                    <span class="text-white/40">••••••</span>{{ substr(preg_replace('/\D/', '', $phone), -3) }}
                                @else
                                    {{ $maskedEmail }}
                                @endif
                            </p>
                        </div>
                        <button type="button" wire:click="goBack"
                            class="flex items-center gap-1.5 border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-medium text-white/50 transition hover:bg-white/8 hover:text-white/80">
                            <flux:icon.arrow-left class="size-3.5" />
                            Change
                        </button>
                    </div>

                    <form wire:submit="verifyOtp" class="flex flex-col items-center gap-5">
                        <flux:otp wire:model="code" length="6" label="Verification Code" label:sr-only
                            :error:icon="false" error:class="text-center" class="mx-auto" />
                        <flux:button variant="primary" type="submit" class="w-full">Verify & Continue</flux:button>
                    </form>

                    <div class="border-t border-white/6 pt-4 text-center"
                        x-data="{
                            countdown: @entangle('resendCooldown'),
                            interval: null,
                            start() {
                                clearInterval(this.interval);
                                this.interval = setInterval(() => {
                                    if (this.countdown > 0) { this.countdown--; }
                                    else { clearInterval(this.interval); }
                                }, 1000);
                            }
                        }"
                        x-init="$watch('countdown', v => { if (v > 0) start() }); start()">

                        <template x-if="countdown > 0">
                            <div class="flex items-center justify-center gap-3">
                                <p class="text-sm text-white/40">Resend in</p>
                                <div class="inline-flex h-8 w-8 items-center justify-center border border-white/10 bg-white/5">
                                    <span class="font-mono text-sm font-semibold text-white/70 tabular-nums" x-text="countdown"></span>
                                </div>
                            </div>
                        </template>

                        <template x-if="countdown <= 0">
                            <button wire:click="resend" wire:loading.attr="disabled"
                                class="text-sm font-medium text-blue-400 transition hover:text-blue-300 disabled:opacity-50">
                                Resend code
                            </button>
                        </template>
                    </div>

                @endif

            </flux:card>

            <p class="mt-5 text-center text-xs text-white/35">
                Remembered it?
                <flux:link href="{{ route('auth.login') }}" wire:navigate class="text-white/60! hover:text-white!">
                    Sign in
                </flux:link>
            </p>

        </div>
    </div>

</div>
