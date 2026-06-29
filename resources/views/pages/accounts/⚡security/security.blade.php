@php $title = 'Security'; @endphp

<div class="space-y-10">

    {{-- ═══════════════════════════════════════════════════════════════
    SECTION · Change Password
    ════════════════════════════════════════════════════════════════ --}}
    <section class="space-y-5">

        <div>
            <flux:heading size="lg" class="text-white!">Password</flux:heading>
            <flux:text class="text-white/40! text-sm!">Update your password. Use a strong, unique password.</flux:text>
        </div>

        <div class="rounded-[1.75rem] border border-white/[0.07] bg-white/3 divide-y divide-white/5">

            {{-- Success banner --}}
            @if ($success)
            <div class="flex items-center gap-3 px-6 py-4">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-500/15">
                    <flux:icon.check-circle class="size-4 text-emerald-400" />
                </div>
                <p class="text-sm text-emerald-400">Password updated successfully.</p>
            </div>
            @endif

            <form wire:submit="updatePassword" class="divide-y divide-white/5">

                {{-- Current password --}}
                <div class="px-6 py-5 space-y-2">
                    <div class="flex items-center justify-between gap-6">
                        <div class="shrink-0">
                            <p class="text-sm font-medium text-white/80">Current password</p>
                            <p class="text-xs text-white/35 mt-0.5">Required to confirm your identity.</p>
                        </div>
                        <div class="w-full max-w-xs space-y-1.5">
                            <flux:input
                                wire:model="current_password"
                                type="password"
                                size="sm"
                                placeholder="••••••••"
                                autocomplete="current-password"
                                viewable
                            />
                            @error('current_password')
                            <p class="text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- New password --}}
                <div class="px-6 py-5 space-y-2">
                    <div class="flex items-center justify-between gap-6">
                        <div class="shrink-0">
                            <p class="text-sm font-medium text-white/80">New password</p>
                            <p class="text-xs text-white/35 mt-0.5">Min 8 chars, mixed case, number, symbol.</p>
                        </div>
                        <div class="w-full max-w-xs space-y-1.5">
                            <flux:input
                                wire:model="password"
                                type="password"
                                size="sm"
                                placeholder="••••••••"
                                autocomplete="new-password"
                                viewable
                            />
                            @error('password')
                            <p class="text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Confirm new password --}}
                <div class="px-6 py-5 space-y-2">
                    <div class="flex items-center justify-between gap-6">
                        <div class="shrink-0">
                            <p class="text-sm font-medium text-white/80">Confirm new password</p>
                            <p class="text-xs text-white/35 mt-0.5">Enter new password again.</p>
                        </div>
                        <div class="w-full max-w-xs space-y-1.5">
                            <flux:input
                                wire:model="password_confirmation"
                                type="password"
                                size="sm"
                                placeholder="••••••••"
                                autocomplete="new-password"
                                viewable
                            />
                            @error('password_confirmation')
                            <p class="text-xs text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Submit --}}
                <div class="px-6 py-4 flex justify-end">
                    <flux:button type="submit" variant="primary" class="rounded-2xl!">
                        <span wire:loading.remove wire:target="updatePassword">Update password</span>
                        <span wire:loading wire:target="updatePassword">Updating…</span>
                    </flux:button>
                </div>

            </form>

        </div>

    </section>

</div>
