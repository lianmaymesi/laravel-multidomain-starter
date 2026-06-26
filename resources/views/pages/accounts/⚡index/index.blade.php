@php $title = 'Profile'; @endphp

<div class="space-y-10">

    <section class="space-y-5">

        {{-- Account status strip --}}
        <div class="grid gap-3 grid-cols-3">

            <div class="flex items-center gap-3 rounded-2xl border border-white/[0.07] bg-white/3 px-4 py-3">
                @if (auth()->user()->hasVerifiedEmail())
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/15">
                    <flux:icon.check-circle class="size-4 text-emerald-400" />
                </div>
                <div>
                    <p class="text-xs font-medium text-white/80">Email</p>
                    <p class="text-xs text-emerald-400">Verified</p>
                </div>
                @else
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500/15">
                    <flux:icon.exclamation-circle class="size-4 text-amber-400" />
                </div>
                <div>
                    <p class="text-xs font-medium text-white/80">Email</p>
                    <p class="text-xs text-amber-400">Not verified</p>
                </div>
                @endif
            </div>

            <div class="flex items-center gap-3 rounded-2xl border border-white/[0.07] bg-white/3 px-4 py-3">
                @if (auth()->user()->hasVerifiedPhone())
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/15">
                    <flux:icon.check-circle class="size-4 text-emerald-400" />
                </div>
                <div>
                    <p class="text-xs font-medium text-white/80">Phone</p>
                    <p class="text-xs text-emerald-400">Verified</p>
                </div>
                @else
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500/15">
                    <flux:icon.exclamation-circle class="size-4 text-amber-400" />
                </div>
                <div>
                    <p class="text-xs font-medium text-white/80">Phone</p>
                    <p class="text-xs text-amber-400">Not verified</p>
                </div>
                @endif
            </div>

            <div class="flex items-center gap-3 rounded-2xl border border-white/[0.07] bg-white/3 px-4 py-3">
                @if (auth()->user()->hasTwoFactorEnabled())
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-500/15">
                    <flux:icon.shield-check class="size-4 text-blue-400" />
                </div>
                <div>
                    <p class="text-xs font-medium text-white/80">2FA</p>
                    <p class="text-xs text-blue-400">Enabled</p>
                </div>
                @else
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/5">
                    <flux:icon.shield-exclamation class="size-4 text-white/30" />
                </div>
                <div>
                    <p class="text-xs font-medium text-white/80">2FA</p>
                    <p class="text-xs text-white/30">Not enabled</p>
                </div>
                @endif
            </div>

        </div>

    </section>

    {{-- ═══════════════════════════════════════════════════════════════
    SECTION 1 · Profile
    ════════════════════════════════════════════════════════════════ --}}
    <section class="space-y-5">

        <div>
            <flux:heading size="lg" class="text-white!">Profile</flux:heading>
            <flux:text class="text-white/40! text-sm!">Your name and public-facing details.</flux:text>
        </div>

        <div class="rounded-[1.75rem] border border-white/[0.07] bg-white/3">

            {{-- Avatar + name header --}}
            <div class="flex items-center gap-5 border-b border-white/[0.07] px-6 py-5">
                <flux:avatar size="xl" name="{{ auth()->user()->name }}" class="shrink-0" />
                <div>
                    <p class="font-semibold text-white">{{ auth()->user()->name }}</p>
                    <p class="text-sm text-white/40">Member since {{ auth()->user()->created_at?->format('M Y') }}</p>
                </div>
            </div>

            {{-- Name row --}}
            <div class="px-6 py-5">
                @if ($editingName)
                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-sm text-white/45 shrink-0">Full name</span>
                        <flux:input wire:model="name" size="sm" class="max-w-xs" wire:keydown.enter="saveName"
                            wire:keydown.escape="cancelName" autofocus />
                    </div>
                    @error('name')
                    <p class="text-xs text-red-400 text-right">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-white/30 text-right">↵ to save · Esc to cancel</p>
                </div>
                @else
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-white/40 mb-0.5">Full name</p>
                        <p class="text-sm text-white/85">{{ auth()->user()->name }}</p>
                    </div>
                    <flux:button icon="pencil-square" wire:click="editName" size="xs" variant="ghost"
                        class="rounded-xl!">
                        Edit
                    </flux:button>
                </div>
                @endif
            </div>

            {{-- Member since --}}
            <div class="border-t border-white/5 px-6 py-4 flex items-center justify-between">
                <p class="text-xs text-white/40">Member since</p>
                <p class="text-sm text-white/85">{{ auth()->user()->created_at?->format('M d, Y') }}</p>
            </div>

        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════
    SECTION 2 · Contact info
    ════════════════════════════════════════════════════════════════ --}}
    <section class="space-y-5">

        <div>
            <flux:heading size="lg" class="text-white!">Contact info</flux:heading>
            <flux:text class="text-white/40! text-sm!">Manage your email and phone. Changes require re-verification.
            </flux:text>
        </div>

        {{-- ── Email card ──────────────────────────────────────────── --}}
        <div class="rounded-[1.75rem] border border-white/[0.07] bg-white/3 divide-y divide-white/5">

            {{-- Current email --}}
            <div class="px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-1">
                        <p class="text-xs text-white/40">Current email</p>
                        <div class="flex items-center gap-2">
                            <p class="text-sm text-white/85">{{ auth()->user()->email }}</p>
                            @if (auth()->user()->hasVerifiedEmail())
                            <span
                                class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-medium text-emerald-400">
                                Verified
                            </span>
                            @else
                            <span
                                class="rounded-full border border-amber-500/20 bg-amber-500/10 px-2 py-0.5 text-[10px] font-medium text-amber-400">
                                Unverified
                            </span>
                            @endif
                            @if (! auth()->user()->hasVerifiedEmail() && ! $editingEmail)
                            <flux:button wire:click="sendEmailVerification" size="xs" variant="primary" color="red"
                                class="rounded-2xl!">
                                Send verification email
                            </flux:button>
                            @endif
                        </div>
                    </div>
                    @if (! $editingEmail && ! auth()->user()->hasPendingEmailChange())
                    <flux:button icon="pencil-square" wire:click="editEmail" size="xs" variant="ghost"
                        class="rounded-xl! shrink-0">
                        Change
                    </flux:button>
                    @endif
                </div>

                {{-- Change form --}}
                @if ($editingEmail)
                <div class="mt-4 space-y-2 border-t border-white/5 pt-4">
                    <p class="text-xs text-white/40">Your current email stays active until the new one is verified.</p>
                    <flux:input wire:model="newEmail" type="email" size="sm" placeholder="new@example.com"
                        wire:keydown.enter="requestEmailChange" wire:keydown.escape="cancelEmail" autofocus />
                    @error('newEmail')
                    <p class="text-xs text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-white/30">↵ to send verification · Esc to cancel</p>
                </div>
                @endif
            </div>

            {{-- Pending email (if any) --}}
            @if (auth()->user()->hasPendingEmailChange())
            <div class="px-6 py-5">
                <div class="space-y-3">
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-1">
                            <p class="text-xs text-white/40">Pending email</p>
                            <div class="flex items-center gap-2">
                                <p class="text-sm text-white/85">{{ auth()->user()->pending_email }}</p>
                                @if (auth()->user()->pendingEmailExpired())
                                <span
                                    class="rounded-full border border-red-500/20 bg-red-500/10 px-2 py-0.5 text-[10px] font-medium text-red-400">
                                    Expired
                                </span>
                                @else
                                <span
                                    class="rounded-full border border-amber-500/20 bg-amber-500/10 px-2 py-0.5 text-[10px] font-medium text-amber-400">
                                    Awaiting verification
                                </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if (session('emailStatus'))
                    <p class="text-xs text-emerald-400">{{ session('emailStatus') }}</p>
                    @else
                    @if (auth()->user()->pendingEmailExpired())
                    <p class="text-xs text-white/35">Verification link expired.</p>
                    @else
                    <p class="text-xs text-white/35">
                        We sent a verification link to <span class="text-white/60">{{ auth()->user()->pending_email
                            }}</span>.
                        Click it to make the new email active.
                    </p>
                    @endif
                    @endif

                    <div class="flex flex-wrap gap-2">
                        <flux:button wire:click="resendEmailVerification" size="sm" variant="ghost"
                            class="rounded-2xl!">
                            <span wire:loading.remove wire:target="resendEmailVerification">Resend verification</span>
                            <span wire:loading wire:target="resendEmailVerification">Sending…</span>
                        </flux:button>
                        <flux:button wire:click="cancelEmailChange" size="sm" variant="ghost"
                            class="rounded-2xl! text-red-400! hover:text-red-300!">
                            Cancel change
                        </flux:button>
                        @if (! $editingEmail)
                        <flux:button icon="pencil-square" wire:click="editEmail" size="sm" variant="ghost"
                            class="rounded-2xl!">
                            Change again
                        </flux:button>
                        @endif
                    </div>

                    {{-- Change again form --}}
                    @if ($editingEmail)
                    <div class="space-y-2 border-t border-white/5 pt-3">
                        <flux:input wire:model="newEmail" type="email" size="sm" placeholder="different@example.com"
                            wire:keydown.enter="requestEmailChange" wire:keydown.escape="cancelEmail" autofocus />
                        @error('newEmail')
                        <p class="text-xs text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-white/30">↵ to send verification · Esc to cancel</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

        </div>

        {{-- ── Phone card ──────────────────────────────────────────── --}}
        <div class="rounded-[1.75rem] border border-white/[0.07] bg-white/3 px-6 py-5">

            @if ($editingPhone)
            <div class="space-y-2">
                <p class="text-xs text-white/40">Enter the new phone number. An OTP will be sent to verify it.</p>
                <flux:input wire:model="newPhone" size="sm" mask="99999-99999" placeholder="98765-43210"
                    wire:keydown.enter="savePhone" wire:keydown.escape="cancelPhone" autofocus />
                @error('newPhone')
                <p class="text-xs text-red-400">{{ $message }}</p>
                @enderror
                <p class="text-xs text-white/30">↵ to send OTP · Esc to cancel</p>
            </div>
            @else
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <p class="text-xs text-white/40">Phone</p>
                    <div class="flex items-center gap-2">
                        <p class="text-sm text-white/85">
                            {{ auth()->user()->country_code }}
                            {{ auth()->user()->phone ? str_repeat('•', max(0, strlen(auth()->user()->phone) - 3)) .
                            substr(auth()->user()->phone, -3) : '—' }}
                        </p>
                        @if (auth()->user()->hasVerifiedPhone())
                        <span
                            class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-medium text-emerald-400">
                            Verified
                        </span>
                        @else
                        <span
                            class="rounded-full border border-amber-500/20 bg-amber-500/10 px-2 py-0.5 text-[10px] font-medium text-amber-400">
                            Unverified
                        </span>
                        @endif
                    </div>
                </div>
                <flux:button icon="pencil-square" wire:click="editPhone" size="xs" variant="ghost"
                    class="rounded-xl! shrink-0">
                    Change
                </flux:button>
            </div>
            @endif

        </div>

    </section>

</div>