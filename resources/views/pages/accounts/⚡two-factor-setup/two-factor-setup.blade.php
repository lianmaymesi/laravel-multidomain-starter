@php $title = 'Two-Factor Authentication'; @endphp

<div class="space-y-8">

    @if (! $confirmed)

    {{-- Page heading --}}
    <div>
        <flux:heading size="xl" class="text-white!">Two-Factor Authentication</flux:heading>
        <flux:text class="text-white/50!">Add an extra layer of security to your account.</flux:text>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_auto]">

        {{-- Left: instructions + code form --}}
        <div class="space-y-5">

            {{-- Step 1 --}}
            <div class="rounded-[1.75rem] border border-white/[0.07] bg-white/3 p-6 space-y-4">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-500/20 text-xs font-semibold text-blue-400">1</span>
                    <p class="text-sm font-medium text-white/80">Scan with your authenticator app</p>
                </div>
                <p class="text-sm text-white/45 pl-9">
                    Open Google Authenticator, Authy, or any TOTP app and scan the QR code shown on the right.
                </p>

                {{-- QR code (visible on mobile, hidden on lg where it's in the right column) --}}
                <div class="flex justify-center pt-2 lg:hidden">
                    <div class="inline-block rounded-2xl border border-white/10 bg-white p-3 shadow-lg">
                        {!! $qrCodeSvg !!}
                    </div>
                </div>
            </div>

            {{-- Step 2: enter code --}}
            <div class="rounded-[1.75rem] border border-white/[0.07] bg-white/3 p-6 space-y-5">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-blue-500/20 text-xs font-semibold text-blue-400">2</span>
                    <p class="text-sm font-medium text-white/80">Enter the 6-digit code to confirm</p>
                </div>

                <form wire:submit="confirm" class="flex flex-col items-center gap-5 pl-9">
                    <flux:otp wire:model="code" length="6" label="Authenticator code" label:sr-only :error:icon="false"
                        error:class="text-center" class="mx-auto" />

                    <flux:button variant="primary" type="submit" class="w-full py-3.5!">
                        <span wire:loading.remove wire:target="confirm">Enable 2FA</span>
                        <span wire:loading wire:target="confirm">Verifying…</span>
                    </flux:button>
                </form>
            </div>

            {{-- Skip --}}
            <p class="text-center text-xs text-white/25">
                <button wire:click="skip" type="button"
                    class="underline underline-offset-2 hover:text-white/50 transition-colors">
                    Skip for now
                </button>
            </p>

        </div>

        {{-- Right: QR code (desktop only) --}}
        <div class="hidden lg:flex lg:items-start lg:pt-1">
            <div class="rounded-2xl border border-white/10 bg-white p-4 shadow-xl">
                {!! $qrCodeSvg !!}
            </div>
        </div>

    </div>

    @elseif ($justConfirmed)

    {{-- Post-setup success: show recovery codes --}}
    <div class="space-y-6">

        <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-emerald-500/15">
                <flux:icon.shield-check class="size-6 text-emerald-400" />
            </div>
            <div>
                <flux:heading size="xl" class="text-white!">2FA Enabled</flux:heading>
                <flux:text class="text-white/50!">Your account is now protected with two-factor authentication.
                </flux:text>
            </div>
        </div>

        <div class="rounded-[1.75rem] border border-white/[0.07] bg-white/3 p-6 space-y-4">

            <div>
                <p class="text-sm font-semibold text-white/80">Recovery Codes</p>
                <p class="text-sm text-white/45 mt-0.5">
                    Save these somewhere safe — each can only be used once.
                </p>
            </div>

            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                @foreach ($recoveryCodes as $code)
                <div class="rounded-xl border border-white/[0.07] bg-white/4 px-3 py-2 text-center">
                    <code class="text-xs font-mono tracking-wider text-white/70">{{ $code }}</code>
                </div>
                @endforeach
            </div>

            <div class="flex items-start gap-2.5 rounded-xl border border-amber-500/20 bg-amber-500/10 px-4 py-3">
                <flux:icon.exclamation-triangle class="mt-0.5 size-4 shrink-0 text-amber-400" />
                <p class="text-xs text-amber-400">
                    Store these codes in a password manager. You won't be able to view them again after leaving this
                    page.
                </p>
            </div>

        </div>

        <flux:button href="{{ auth()->user()?->redirect() }}" variant="primary" class="py-3.5!">
            Continue to dashboard
            <flux:icon.arrow-right class="ml-1 size-4" />
        </flux:button>

    </div>

    @else

    {{-- Management page: 2FA already enabled --}}
    <div class="space-y-6">

        {{-- Heading --}}
        <div>
            <flux:heading size="xl" class="text-white!">Two-Factor Authentication</flux:heading>
            <flux:text class="text-white/50!">Manage your two-factor authentication settings.</flux:text>
        </div>

        {{-- Status --}}
        <div class="rounded-[1.75rem] border border-emerald-500/20 bg-emerald-500/5 p-5 flex items-center gap-4">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-500/15">
                <flux:icon.shield-check class="size-5 text-emerald-400" />
            </div>
            <div>
                <p class="text-sm font-semibold text-white/80">Status</p>
                <p class="text-xs text-emerald-400 font-medium mt-0.5">Enabled — your account is protected</p>
            </div>
        </div>

        {{-- Authenticator App --}}
        <div class="rounded-[1.75rem] border border-white/[0.07] bg-white/3 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-semibold text-white/80">Authenticator App</p>
                    <p class="text-xs text-white/40 mt-0.5">
                        Connected
                        @if (auth()->user()->two_factor_enabled_at)
                        · Enabled {{ auth()->user()->two_factor_enabled_at->diffForHumans() }}
                        @endif
                    </p>
                </div>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/15 px-2.5 py-1 text-xs font-medium text-emerald-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                    Active
                </span>
            </div>
        </div>

        {{-- Recovery Codes --}}
        <div class="rounded-[1.75rem] border border-white/[0.07] bg-white/3 p-6 space-y-4">
            <div>
                <p class="text-sm font-semibold text-white/80">Recovery Codes</p>
                <p class="text-xs text-white/40 mt-0.5">Use these if you lose access to your authenticator app.</p>
            </div>

            @if ($showRecoveryCodes && count($recoveryCodes))
            <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                @foreach ($recoveryCodes as $recoveryCode)
                <div class="rounded-xl border border-white/[0.07] bg-white/4 px-3 py-2 text-center">
                    <code class="text-xs font-mono tracking-wider text-white/70">{{ $recoveryCode }}</code>
                </div>
                @endforeach
            </div>

            <div class="flex items-start gap-2.5 rounded-xl border border-amber-500/20 bg-amber-500/10 px-4 py-3">
                <flux:icon.exclamation-triangle class="mt-0.5 size-4 shrink-0 text-amber-400" />
                <p class="text-xs text-amber-400">Store these codes in a password manager. Each code can only be used
                    once.</p>
            </div>
            @endif

            <div class="flex flex-wrap gap-2">
                @if ($showRecoveryCodes)
                <flux:button icon="eye-slash" wire:click="hideRecoveryCodes" size="sm" variant="ghost">
                    Hide codes
                </flux:button>
                @else
                <flux:button icon="eye" wire:click="viewRecoveryCodes" size="sm" variant="ghost">
                    View recovery codes
                </flux:button>
                @endif
                <flux:button icon="arrow-path" wire:click="regenerateRecoveryCodes"
                    wire:confirm="Regenerate recovery codes? Your existing codes will stop working immediately."
                    size="sm" variant="ghost">
                    Regenerate codes
                </flux:button>
            </div>
        </div>

        {{-- Actions --}}
        <div class="rounded-[1.75rem] border border-white/[0.07] bg-white/3 p-6 space-y-4">
            <p class="text-sm font-semibold text-white/80">Actions</p>

            <div class="flex flex-wrap gap-2">
                <flux:button icon="qr-code" wire:click="reconfigure" size="sm" variant="ghost">
                    Reconfigure authenticator
                </flux:button>
                <flux:button wire:click="disable"
                    wire:confirm="Disable two-factor authentication? Your account will be less secure." size="sm"
                    variant="danger">
                    Disable 2FA
                </flux:button>
            </div>
        </div>

        <flux:button href="{{ auth()->user()?->redirect() }}" variant="primary" class="py-3.5!">
            Continue to dashboard
            <flux:icon.arrow-right class="ml-1 size-4" />
        </flux:button>

    </div>

    @endif

</div>