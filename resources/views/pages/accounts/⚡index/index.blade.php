@php $title = 'Profile'; @endphp

<div class="space-y-10">

    {{-- Deletion cancelled notice --}}
    @if (session('deletion_cancelled'))
    <div class="rounded-[1.75rem] border border-emerald-500/20 bg-emerald-500/[0.07] p-5 flex items-start gap-3">
        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/15">
            <flux:icon.check-circle class="size-4 text-emerald-400" />
        </div>
        <div class="space-y-1">
            <p class="text-sm font-medium text-emerald-300">Account deletion cancelled</p>
            <p class="text-xs text-zinc-500 dark:text-white/55">Welcome back! Your account is fully active and the deletion has been
                cancelled.</p>
        </div>
    </div>
    @endif

    <section class="space-y-5">

        {{-- Account status strip --}}
        <div class="grid gap-3 {{ config('multidomain.phone_verification_enabled') ? 'grid-cols-3' : 'grid-cols-2' }}">

            <div class="flex items-center gap-3 rounded-2xl border border-zinc-200 dark:border-white/[0.07] bg-zinc-50 dark:bg-white/3 px-4 py-3">
                @if (auth()->user()->hasVerifiedEmail())
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/15">
                    <flux:icon.check-circle class="size-4 text-emerald-400" />
                </div>
                <div>
                    <p class="text-xs font-medium text-zinc-800 dark:text-white/80">Email</p>
                    <p class="text-xs text-emerald-400">Verified</p>
                </div>
                @else
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500/15">
                    <flux:icon.exclamation-circle class="size-4 text-amber-400" />
                </div>
                <div>
                    <p class="text-xs font-medium text-zinc-800 dark:text-white/80">Email</p>
                    <p class="text-xs text-amber-400">Not verified</p>
                </div>
                @endif
            </div>

            @if (config('multidomain.phone_verification_enabled'))
            <div class="flex items-center gap-3 rounded-2xl border border-zinc-200 dark:border-white/[0.07] bg-zinc-50 dark:bg-white/3 px-4 py-3">
                @if (auth()->user()->hasVerifiedPhone())
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/15">
                    <flux:icon.check-circle class="size-4 text-emerald-400" />
                </div>
                <div>
                    <p class="text-xs font-medium text-zinc-800 dark:text-white/80">Phone</p>
                    <p class="text-xs text-emerald-400">Verified</p>
                </div>
                @else
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-500/15">
                    <flux:icon.exclamation-circle class="size-4 text-amber-400" />
                </div>
                <div>
                    <p class="text-xs font-medium text-zinc-800 dark:text-white/80">Phone</p>
                    <p class="text-xs text-amber-400">Not verified</p>
                </div>
                @endif
            </div>
            @endif

            <div class="flex items-center gap-3 rounded-2xl border border-zinc-200 dark:border-white/[0.07] bg-zinc-50 dark:bg-white/3 px-4 py-3">
                @if (auth()->user()->hasTwoFactorEnabled())
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-500/15">
                    <flux:icon.shield-check class="size-4 text-blue-600 dark:text-blue-400" />
                </div>
                <div>
                    <p class="text-xs font-medium text-zinc-800 dark:text-white/80">2FA</p>
                    <p class="text-xs text-blue-600 dark:text-blue-400">Enabled</p>
                </div>
                @else
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-white/5">
                    <flux:icon.shield-exclamation class="size-4 text-zinc-400 dark:text-white/30" />
                </div>
                <div>
                    <p class="text-xs font-medium text-zinc-800 dark:text-white/80">2FA</p>
                    <p class="text-xs text-zinc-400 dark:text-white/30">Not enabled</p>
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
            <flux:heading size="lg" class="text-zinc-900! dark:text-white!">Profile</flux:heading>
            <flux:text class="text-zinc-500! dark:text-white/40! text-sm!">Your name and public-facing details.</flux:text>
        </div>

        <div class="rounded-[1.75rem] border border-zinc-200 dark:border-white/[0.07] bg-zinc-50 dark:bg-white/3">

            {{-- Avatar + name header --}}
            <div class="flex items-center gap-5 border-b border-zinc-200 dark:border-white/[0.07] px-6 py-5">
                <flux:avatar size="xl" name="{{ auth()->user()->name }}" class="shrink-0" />
                <div>
                    <p class="font-semibold text-zinc-900 dark:text-white">{{ auth()->user()->name }}</p>
                    <p class="text-sm text-zinc-500 dark:text-white/40">Member since {{ auth()->user()->created_at?->format('M Y') }}</p>
                </div>
            </div>

            {{-- Name row --}}
            <div class="px-6 py-5">
                @if ($editingName)
                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-4">
                        <span class="text-sm text-zinc-500 dark:text-white/45 shrink-0">Full name</span>
                        <flux:input wire:model="name" size="sm" class="max-w-xs" wire:keydown.enter="saveName"
                            wire:keydown.escape="cancelName" x-on:focus-name-input.window="$el.focus()" />
                    </div>
                    @error('name')
                    <p class="text-xs text-red-400 text-right">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-zinc-400 dark:text-white/30 text-right">↵ to save · Esc to cancel</p>
                </div>
                @else
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-white/40 mb-0.5">Full name</p>
                        <p class="text-sm text-zinc-800 dark:text-white/85">{{ auth()->user()->name }}</p>
                    </div>
                    <flux:button icon="pencil-square" wire:click="editName" size="xs" variant="ghost">
                        Edit
                    </flux:button>
                </div>
                @endif
            </div>

            {{-- Member since --}}
            <div class="border-t border-zinc-200 dark:border-white/5 px-6 py-4 flex items-center justify-between">
                <p class="text-xs text-zinc-500 dark:text-white/40">Member since</p>
                <p class="text-sm text-zinc-800 dark:text-white/85">{{ auth()->user()->created_at?->format('M d, Y') }}</p>
            </div>

        </div>
    </section>

    {{-- ═══════════════════════════════════════════════════════════════
    SECTION 2 · Contact info
    ════════════════════════════════════════════════════════════════ --}}
    <section class="space-y-5">

        <div>
            <flux:heading size="lg" class="text-zinc-900! dark:text-white!">Contact info</flux:heading>
            <flux:text class="text-zinc-500! dark:text-white/40! text-sm!">
                @if (config('multidomain.phone_verification_enabled'))
                Manage your email and phone. Changes require re-verification.
                @else
                Manage your email. Changes require re-verification.
                @endif
            </flux:text>
        </div>

        {{-- ── Email card ──────────────────────────────────────────── --}}
        <div class="rounded-[1.75rem] border border-zinc-200 dark:border-white/[0.07] bg-zinc-50 dark:bg-white/3 divide-y divide-zinc-200 dark:divide-white/5">

            {{-- Current email --}}
            <div class="px-6 py-5">
                <div class="flex items-start justify-between gap-4">
                    <div class="space-y-1">
                        <p class="text-xs text-zinc-500 dark:text-white/40">Current email</p>
                        <div class="flex items-center gap-2">
                            <p class="text-sm text-zinc-800 dark:text-white/85">{{ auth()->user()->email }}</p>
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
                            <flux:button wire:click="sendEmailVerification" size="xs" variant="primary" color="red">
                                Send verification email
                            </flux:button>
                            @endif
                        </div>
                    </div>
                    @if (! $editingEmail && ! auth()->user()->hasPendingEmailChange())
                    <flux:button icon="pencil-square" wire:click="editEmail" size="xs" variant="ghost"
                        class="shrink-0">
                        Change
                    </flux:button>
                    @endif
                </div>

                {{-- Change form --}}
                @if ($editingEmail)
                <div class="mt-4 space-y-2 border-t border-zinc-200 dark:border-white/5 pt-4">
                    <p class="text-xs text-zinc-500 dark:text-white/40">Your current email stays active until the new one is verified.</p>
                    <flux:input wire:model="newEmail" type="email" size="sm" placeholder="new@example.com"
                        wire:keydown.enter="requestEmailChange" wire:keydown.escape="cancelEmail"
                        x-on:focus-email-input.window="$el.focus()" />
                    @error('newEmail')
                    <p class="text-xs text-red-400">{{ $message }}</p>
                    @enderror
                    <p class="text-xs text-zinc-400 dark:text-white/30">↵ to send verification · Esc to cancel</p>
                </div>
                @endif
            </div>

            {{-- Pending email (if any) --}}
            @if (auth()->user()->hasPendingEmailChange())
            <div class="px-6 py-5">
                <div class="space-y-3">
                    <div class="flex items-start justify-between gap-4">
                        <div class="space-y-1">
                            <p class="text-xs text-zinc-500 dark:text-white/40">Pending email</p>
                            <div class="flex items-center gap-2">
                                <p class="text-sm text-zinc-800 dark:text-white/85">{{ auth()->user()->pending_email }}</p>
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
                    <p class="text-xs text-zinc-400 dark:text-white/35">Verification link expired.</p>
                    @else
                    <p class="text-xs text-zinc-400 dark:text-white/35">
                        We sent a verification link to <span class="text-zinc-600 dark:text-white/60">{{ auth()->user()->pending_email
                            }}</span>.
                        Click it to make the new email active.
                    </p>
                    @endif
                    @endif

                    <div class="flex flex-wrap gap-2">
                        <flux:button wire:click="resendEmailVerification" size="sm" variant="ghost">
                            <span wire:loading.remove wire:target="resendEmailVerification">Resend verification</span>
                            <span wire:loading wire:target="resendEmailVerification">Sending…</span>
                        </flux:button>
                        <flux:button wire:click="cancelEmailChange" size="sm" variant="ghost"
                            class="text-red-400! hover:text-red-300!">
                            Cancel change
                        </flux:button>
                        @if (! $editingEmail)
                        <flux:button icon="pencil-square" wire:click="editEmail" size="sm" variant="ghost">
                            Change again
                        </flux:button>
                        @endif
                    </div>

                    {{-- Change again form --}}
                    @if ($editingEmail)
                    <div class="space-y-2 border-t border-zinc-200 dark:border-white/5 pt-3">
                        <flux:input wire:model="newEmail" type="email" size="sm" placeholder="different@example.com"
                            wire:keydown.enter="requestEmailChange" wire:keydown.escape="cancelEmail" autofocus />
                        @error('newEmail')
                        <p class="text-xs text-red-400">{{ $message }}</p>
                        @enderror
                        <p class="text-xs text-zinc-400 dark:text-white/30">↵ to send verification · Esc to cancel</p>
                    </div>
                    @endif
                </div>
            </div>
            @endif

        </div>

        {{-- ── Phone card ──────────────────────────────────────────────────────────── --}}
        @if (config('multidomain.phone_verification_enabled'))
        <div class="rounded-[1.75rem] border border-zinc-200 dark:border-white/[0.07] bg-zinc-50 dark:bg-white/3 px-6 py-5">

            @if ($editingPhone)
            <div class="space-y-2">
                <p class="text-xs text-zinc-500 dark:text-white/40">Enter the new phone number. An OTP will be sent to verify it.</p>
                <flux:input wire:model="newPhone" size="sm" mask="99999-99999" placeholder="98765-43210"
                    wire:keydown.enter="savePhone" wire:keydown.escape="cancelPhone"
                    x-on:focus-phone-input.window="$el.focus()" />
                @error('newPhone')
                <p class="text-xs text-red-400">{{ $message }}</p>
                @enderror
                <p class="text-xs text-zinc-400 dark:text-white/30">↵ to send OTP · Esc to cancel</p>
            </div>
            @else
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-1">
                    <p class="text-xs text-zinc-500 dark:text-white/40">Phone</p>
                    <div class="flex items-center gap-2">
                        <p class="text-sm text-zinc-800 dark:text-white/85">
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
                    class="shrink-0">
                    Change
                </flux:button>
            </div>
            @endif

        </div>
        @endif

    </section>

    {{-- ═══════════════════════════════════════════════════════════════
    SECTION 3 · Delete account
    ════════════════════════════════════════════════════════════════ --}}
    <section class="space-y-5">

        <div>
            <flux:heading size="lg" class="text-zinc-900! dark:text-white!">Delete account</flux:heading>
            <flux:text class="text-zinc-500! dark:text-white/40! text-sm!">Permanently remove your personal data. This cannot be undone.
            </flux:text>
        </div>

        <div class="rounded-[1.75rem] border border-red-500/10 bg-red-500/[0.03] divide-y divide-zinc-200 dark:divide-white/5">

            @if ($deletionRequest)

            {{-- Pending deletion banner --}}
            <div class="px-6 py-5 space-y-4">
                <div class="flex items-start gap-3">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-500/15">
                        <flux:icon.clock class="size-4 text-red-400" />
                    </div>
                    <div class="space-y-1">
                        <p class="text-sm font-medium text-zinc-900 dark:text-white/90">Account deletion scheduled</p>
                        <p class="text-xs text-zinc-500 dark:text-white/50">
                            Your account will be permanently deleted on
                            <span class="text-zinc-800 dark:text-white/80">{{ $deletionRequest->scheduled_at->format('F j, Y') }}</span>.
                            @if ($deletionRequest->daysRemaining() > 0)
                            <span class="text-red-400">{{ $deletionRequest->daysRemaining() }} {{ Str::plural('day',
                                $deletionRequest->daysRemaining()) }} remaining.</span>
                            @else
                            Processing soon.
                            @endif
                        </p>
                    </div>
                </div>

                @if ($deletionRequest->isCancellable())
                <flux:button wire:click="cancelDeletion" icon="x-circle"
                    wire:confirm="Cancel the account deletion? Your account will remain fully active." size="sm"
                    variant="ghost" class="text-emerald-400! hover:text-emerald-300!">
                    Cancel deletion
                </flux:button>
                @endif
            </div>

            @else

            {{-- Delete request form --}}
            <div class="px-6 py-5 space-y-4">
                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 lg:gap-3">
                    <div class="flex gap-3 max-w-2xl">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-500/15">
                            <flux:icon.trash class="size-4 text-red-400" />
                        </div>
                        <div class="space-y-1">
                            <p class="text-sm font-medium text-zinc-900 dark:text-white/90">Request account deletion</p>
                            <p class="text-xs text-zinc-500 dark:text-white/50 space-y-1">
                                You'll be signed out of all sessions immediately. Your account enters a
                                <span class="text-zinc-700 dark:text-white/70">{{ \App\Models\AccountDeletionRequest::GRACE_PERIOD_DAYS
                                    }}-day cooling period</span>
                                before personal data is deleted.<br>
                                To cancel, simply <span class="text-zinc-700 dark:text-white/70">sign back in</span> during this period —
                                logging in cancels the deletion automatically. After the cooling period your account
                                will be permanently gone and you won't be able to sign in.<br>
                                <a href="{{ route('account.export') }}" wire:navigate
                                    class="text-zinc-600 dark:text-white/60 underline underline-offset-2">Export your data</a> first if
                                needed.
                            </p>
                        </div>
                    </div>

                    @if (! $showDeleteConfirm)
                    <flux:button wire:click="$set('showDeleteConfirm', true)" size="sm" variant="primary" color="red"
                        class="rounded-none">
                        Delete my account
                    </flux:button>
                    @endif
                </div>
                @if ($showDeleteConfirm)
                <div class="space-y-3 rounded-2xl border border-red-500/15 bg-red-500/5 p-4">
                    <p class="text-xs text-zinc-600 dark:text-white/60">Enter your password to confirm. You'll have 30 days to cancel
                        before any data is removed.</p>
                    <div class="space-y-1.5">
                        <flux:input wire:model="deletePassword" type="password" size="sm" placeholder="Your password"
                            autocomplete="current-password" viewable
                            x-on:keydown.escape="$wire.set('showDeleteConfirm', false)" />
                        @error('deletePassword')
                        <p class="text-xs text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:button wire:click="requestDeletion" size="sm" variant="danger" class="rounded-none">
                            <span wire:loading.remove wire:target="requestDeletion">Confirm deletion</span>
                            <span wire:loading wire:target="requestDeletion">Scheduling…</span>
                        </flux:button>
                        <flux:button wire:click="$set('showDeleteConfirm', false)" size="sm" variant="ghost"
                            class="rounded-none">
                            Cancel
                        </flux:button>
                    </div>
                </div>
                @endif
            </div>

            @endif

        </div>

    </section>

</div>