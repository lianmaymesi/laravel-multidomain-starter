@php $title = 'Forgot Password'; @endphp

<div class="min-h-screen bg-zinc-950 flex items-center justify-center px-4 py-8">

    <div class="fixed inset-0 pointer-events-none">
        <div
            class="absolute inset-0 bg-[radial-gradient(circle_at_top,_rgba(59,130,246,0.18),_transparent_35%),radial-gradient(circle_at_20%_80%,_rgba(16,185,129,0.12),_transparent_28%)]">
        </div>
    </div>

    <div class="relative mx-auto w-full max-w-md space-y-6">

        {{-- Header --}}
        <div class="inline-flex items-center gap-3 text-white transition hover:text-white/90">
            <span
                class="flex h-14 w-14 items-center justify-center rounded-2xl border border-white/10 bg-white/5 backdrop-blur-sm">
                <img src="{{ Vite::asset('resources/assets/images/logo.svg') }}" alt="{{ config('app.name') }}"
                    class="h-10 w-auto" />
            </span>
            <div>
                <flux:heading size="xl" class="text-3xl! font-semibold tracking-tight! text-white sm:text-4xl!">
                    Reset password
                </flux:heading>
                <flux:text class="hidden md:block text-sm leading-7 text-white/65">
                    @if (! $otpSent)
                    Verify your identity to continue
                    @else
                    Enter the code we sent to verify it's you
                    @endif
                </flux:text>
            </div>
        </div>

        {{-- Card --}}
        <div
            class="rounded-[1.75rem] border border-white/10 bg-white/5 p-6 shadow-2xl shadow-black/20 backdrop-blur-md space-y-5">

            {{-- Flash --}}
            @if (session('status'))
            <div
                class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-400 text-center">
                {{ session('status') }}
            </div>
            @endif

            {{-- ── STEP 1: Choose method & send OTP ── --}}
            @if (! $otpSent)

            {{-- Tabs --}}
            <div class="flex rounded-xl border border-white/10 bg-white/4 p-1 gap-1">
                <button type="button" wire:click="switchTab('phone')"
                    @class([ 'flex-1 rounded-lg py-2 text-sm font-medium transition-all duration-150'
                    , 'bg-white/10 text-white shadow-sm'=> $activeTab === 'phone',
                    'text-white/45 hover:text-white/70' => $activeTab !== 'phone',
                    ])>
                    Phone
                </button>
                <button type="button" wire:click="switchTab('email')"
                    @class([ 'flex-1 rounded-lg py-2 text-sm font-medium transition-all duration-150'
                    , 'bg-white/10 text-white shadow-sm'=> $activeTab === 'email',
                    'text-white/45 hover:text-white/70' => $activeTab !== 'email',
                    ])>
                    Email
                </button>
            </div>

            <form wire:submit="sendOtp" class="space-y-4">

                {{-- Phone fields --}}
                @if ($activeTab === 'phone')
                <flux:input wire:model="phone" placeholder="98765-43210" mask="99999-99999" />
                @endif

                {{-- Email field --}}
                @if ($activeTab === 'email')
                <flux:input wire:model="email" type="email" placeholder="Enter your registered email"
                    autocomplete="email" />
                @endif

                <flux:button type="submit" variant="primary" class="w-full rounded-3xl! py-3.5!">
                    Send Code
                </flux:button>

            </form>

            {{-- ── STEP 2: Verify OTP ── --}}
            @else

            {{-- Sent-to indicator with back/change button --}}
            <div class="flex items-center justify-between rounded-xl border border-white/10 bg-white/4 px-4 py-3">
                <div>
                    <p class="text-xs text-white/35 mb-0.5">Code sent to</p>
                    <p class="text-sm font-medium text-white/85">
                        @if ($activeTab === 'phone')
                        {{ $country_code }} <span class="text-white/40">••••••</span>{{ substr(preg_replace('/\D/', '',
                        $phone), -3) }}
                        @else
                        {{ $maskedEmail }}
                        @endif
                    </p>
                </div>
                <button type="button" wire:click="goBack"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-white/10 bg-white/5 px-3 py-1.5 text-xs font-medium text-white/50 transition hover:bg-white/10 hover:text-white/80">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Change
                </button>
            </div>

            {{-- OTP input --}}
            <form wire:submit="verifyOtp" class="flex flex-col items-center gap-5">
                <flux:otp wire:model="code" length="6" label="Verification Code" label:sr-only :error:icon="false"
                    error:class="text-center" class="mx-auto" />

                <flux:button variant="primary" type="submit" class="w-full rounded-3xl! py-3.5!">
                    Verify & Continue
                </flux:button>
            </form>

            {{-- Resend countdown --}}
            <div class="text-center border-t border-white/6 pt-4" x-data="{
                    countdown: @entangle('resendCooldown'),
                    interval: null,
                    start() {
                        clearInterval(this.interval);
                        this.interval = setInterval(() => {
                            if (this.countdown > 0) {
                                this.countdown--;
                            } else {
                                clearInterval(this.interval);
                            }
                        }, 1000);
                    }
                }" x-init="$watch('countdown', value => { if (value > 0) start() }); start()">
                <template x-if="countdown > 0">
                    <div class="flex items-center justify-center gap-3">
                        <p class="text-sm text-white/40">Resend code in</p>
                        <div
                            class="inline-flex items-center justify-center w-10 h-10 rounded-full border border-white/10 bg-white/5">
                            <span class="text-sm font-mono font-semibold text-white/70" x-text="countdown"></span>
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

        </div>

        <flux:subheading class="text-center text-white/60">
            Remembered it?
            <flux:link href="{{ route('auth.login') }}" class="text-white" wire:navigate>Sign in</flux:link>
        </flux:subheading>

    </div>
</div>