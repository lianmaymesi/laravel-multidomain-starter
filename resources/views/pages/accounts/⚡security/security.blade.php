@php $title = __('Security'); @endphp

<div class="space-y-10">

    {{-- ═══════════════════════════════════════════════════════════════
    SECTION · Change Password
    ════════════════════════════════════════════════════════════════ --}}
    <section class="space-y-5">

        <div>
            <flux:heading size="lg" class="text-zinc-900! dark:text-white!">{{ __('Password') }}</flux:heading>
            <flux:text class="text-zinc-500! dark:text-white/40! text-sm!">{{ __('Update your password. Use a strong, unique password.') }}</flux:text>
        </div>

        <div class="rounded-[1.75rem] border border-zinc-200 dark:border-white/[0.07] bg-zinc-50 dark:bg-white/3 divide-y divide-zinc-200 dark:divide-white/5">

            @if ($passwordSuccess)
            <div class="flex items-center gap-3 px-6 py-4">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/15">
                    <flux:icon.check-circle class="size-4 text-emerald-400" />
                </div>
                <p class="text-sm text-emerald-400">{{ __('Password updated successfully.') }}</p>
            </div>
            @endif

            <form wire:submit="updatePassword" class="divide-y divide-zinc-200 dark:divide-white/5">

                <div class="px-6 py-5">
                    <div class="flex items-center justify-between gap-6">
                        <div class="shrink-0">
                            <p class="text-sm font-medium text-zinc-800 dark:text-white/80">{{ __('Current password') }}</p>
                            <p class="text-xs text-zinc-400 dark:text-white/35 mt-0.5">{{ __('Required to confirm your identity.') }}</p>
                        </div>
                        <div class="w-full max-w-xs space-y-1.5">
                            <flux:input wire:model="current_password" type="password" size="sm" placeholder="••••••••"
                                autocomplete="current-password" viewable />
                            @error('current_password')
                            <p class="text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="px-6 py-5">
                    <div class="flex items-center justify-between gap-6">
                        <div class="shrink-0">
                            <p class="text-sm font-medium text-zinc-800 dark:text-white/80">{{ __('New password') }}</p>
                            <p class="text-xs text-zinc-400 dark:text-white/35 mt-0.5">{{ __('Min 8 chars, mixed case, number, symbol.') }}</p>
                        </div>
                        <div class="w-full max-w-xs space-y-1.5">
                            <flux:input wire:model="password" type="password" size="sm" placeholder="••••••••"
                                autocomplete="new-password" viewable />
                            @error('password')
                            <p class="text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="px-6 py-5">
                    <div class="flex items-center justify-between gap-6">
                        <div class="shrink-0">
                            <p class="text-sm font-medium text-zinc-800 dark:text-white/80">{{ __('Confirm new password') }}</p>
                            <p class="text-xs text-zinc-400 dark:text-white/35 mt-0.5">{{ __('Enter new password again.') }}</p>
                        </div>
                        <div class="w-full max-w-xs space-y-1.5">
                            <flux:input wire:model="password_confirmation" type="password" size="sm"
                                placeholder="••••••••" autocomplete="new-password" viewable />
                            @error('password_confirmation')
                            <p class="text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 flex justify-end">
                    <flux:button type="submit" variant="primary">
                        <span wire:loading.remove wire:target="updatePassword">{{ __('Update password') }}</span>
                        <span wire:loading wire:target="updatePassword">{{ __('Updating…') }}</span>
                    </flux:button>
                </div>

            </form>

        </div>

    </section>

    {{-- ═══════════════════════════════════════════════════════════════
    SECTION · Active Sessions
    ════════════════════════════════════════════════════════════════ --}}
    <section class="space-y-5">

        <div class="flex items-center justify-between gap-4">
            <div>
                <flux:heading size="lg" class="text-zinc-900! dark:text-white!">{{ __('Login sessions') }}</flux:heading>
                <flux:text class="text-zinc-500! dark:text-white/40! text-sm!">{{ __('Devices currently signed in to your account.') }}</flux:text>
            </div>

            @if ($this->sessions()->where('is_current', false)->isNotEmpty())
            <flux:button variant="ghost" size="sm" wire:click="logoutOtherSessions"
                wire:confirm="{{ __('Log out of all other sessions? Every other device will be signed out immediately.') }}">
                {{ __('Log out other sessions') }}
            </flux:button>
            @endif
        </div>

        <div class="rounded-[1.75rem] border border-zinc-200 dark:border-white/[0.07] bg-zinc-50 dark:bg-white/3 divide-y divide-zinc-200 dark:divide-white/5">

            @foreach ($this->sessions() as $session)
            @php $agent = $this->describeUserAgent($session->user_agent); @endphp
            <div class="flex items-center gap-4 px-6 py-4" wire:key="session-{{ $session->id }}">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-zinc-100 dark:bg-white/5">
                    <flux:icon :name="$agent['icon']" class="size-4 text-zinc-500 dark:text-white/50" />
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <p class="text-sm font-medium text-zinc-800 dark:text-white/80">{{ $agent['label'] }}</p>
                        @if ($session->is_current)
                        <flux:badge size="sm" color="emerald">{{ __('This device') }}</flux:badge>
                        @endif
                    </div>
                    <p class="text-xs text-zinc-400 dark:text-white/35 mt-0.5">
                        {{ $session->ip_address ?? __('Unknown IP') }} ·
                        {{ __('Active :diff', ['diff' => \Illuminate\Support\Carbon::createFromTimestamp($session->last_activity)->diffForHumans()]) }}
                    </p>
                </div>

                @unless ($session->is_current)
                <flux:button variant="ghost" size="sm" wire:click="logoutSession('{{ $session->id }}')"
                    wire:confirm="{{ __('Log out this session?') }}">
                    {{ __('Log out') }}
                </flux:button>
                @endunless
            </div>
            @endforeach

        </div>

    </section>

</div>