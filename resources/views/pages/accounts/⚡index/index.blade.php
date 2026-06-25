@php $title = 'Profile'; @endphp

<div class="space-y-8">

    {{-- Page heading --}}
    <div>
        <flux:heading size="xl" class="text-white!">Profile</flux:heading>
        <flux:text class="text-white/50!">Manage your personal information.</flux:text>
    </div>

    {{-- Account status strip --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">

        {{-- Email verification --}}
        <div class="flex items-center gap-3 rounded-2xl border border-white/[0.07] bg-white/[0.03] px-4 py-3">
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

        {{-- Phone verification --}}
        <div class="flex items-center gap-3 rounded-2xl border border-white/[0.07] bg-white/[0.03] px-4 py-3">
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

        {{-- 2FA --}}
        <div class="flex items-center gap-3 rounded-2xl border border-white/[0.07] bg-white/[0.03] px-4 py-3">
            @if (auth()->user()->hasTwoFactorEnabled())
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-500/15">
                    <flux:icon.shield-check class="size-4 text-blue-400" />
                </div>
                <div>
                    <p class="text-xs font-medium text-white/80">Two-Factor</p>
                    <p class="text-xs text-blue-400">Enabled</p>
                </div>
            @else
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white/[0.05]">
                    <flux:icon.shield-exclamation class="size-4 text-white/30" />
                </div>
                <div>
                    <p class="text-xs font-medium text-white/80">Two-Factor</p>
                    <p class="text-xs text-white/30">Not enabled</p>
                </div>
            @endif
        </div>

    </div>

    {{-- Profile card --}}
    <div class="rounded-[1.75rem] border border-white/[0.07] bg-white/[0.03]">

        {{-- Avatar section --}}
        <div class="flex items-center gap-5 border-b border-white/[0.07] px-6 py-5">
            <flux:avatar size="xl" name="{{ auth()->user()->name }}" class="shrink-0" />
            <div>
                <p class="font-semibold text-white">{{ auth()->user()->name }}</p>
                <p class="text-sm text-white/45">{{ auth()->user()->email }}</p>
            </div>
        </div>

        {{-- Info rows --}}
        <dl class="divide-y divide-white/[0.05]">

            <div class="flex items-center justify-between px-6 py-4">
                <dt class="text-sm text-white/45 w-32 shrink-0">Full name</dt>
                <dd class="text-sm text-white/85 text-right">{{ auth()->user()->name }}</dd>
            </div>

            <div class="flex items-center justify-between px-6 py-4">
                <dt class="text-sm text-white/45 w-32 shrink-0">Email</dt>
                <dd class="flex items-center gap-2 text-right">
                    <span class="text-sm text-white/85">{{ auth()->user()->email }}</span>
                    @if (auth()->user()->hasVerifiedEmail())
                        <span class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-medium text-emerald-400">
                            Verified
                        </span>
                    @endif
                </dd>
            </div>

            <div class="flex items-center justify-between px-6 py-4">
                <dt class="text-sm text-white/45 w-32 shrink-0">Phone</dt>
                <dd class="flex items-center gap-2 text-right">
                    <span class="text-sm text-white/85">
                        {{ auth()->user()->country_code }}
                        {{ auth()->user()->phone ? str_repeat('•', max(0, strlen(auth()->user()->phone) - 3)) . substr(auth()->user()->phone, -3) : '—' }}
                    </span>
                    @if (auth()->user()->hasVerifiedPhone())
                        <span class="rounded-full border border-emerald-500/20 bg-emerald-500/10 px-2 py-0.5 text-[10px] font-medium text-emerald-400">
                            Verified
                        </span>
                    @endif
                </dd>
            </div>

            <div class="flex items-center justify-between px-6 py-4">
                <dt class="text-sm text-white/45 w-32 shrink-0">Member since</dt>
                <dd class="text-sm text-white/85 text-right">
                    {{ auth()->user()->created_at?->format('M d, Y') }}
                </dd>
            </div>

        </dl>

    </div>

</div>
