@php $title = 'Two-Factor Authentication'; @endphp

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
                <p class="mb-1 text-[10px] tracking-[0.25em] uppercase text-blue-400/55">Security check</p>
                <h1 class="text-2xl font-bold text-white">Two-factor check</h1>
                <p class="mt-1 text-sm text-white/40">Confirm it's you to finish signing in</p>
            </div>

            <flux:card class="p-5! space-y-5">

                {{-- Info banner --}}
                <div class="flex items-center gap-3 border border-blue-400/20 bg-blue-400/5 px-4 py-3">
                    <flux:icon.shield-check class="size-4 shrink-0 text-blue-300" />
                    <div>
                        <p class="text-sm font-medium text-white">
                            {{ $usingRecovery ? 'Use a recovery code' : 'Authenticator code required' }}
                        </p>
                        <p class="mt-0.5 text-xs text-white/45">
                            {{ $usingRecovery ? 'Enter one of your emergency recovery codes.' : 'Open your authenticator app and enter the 6-digit code.' }}
                        </p>
                    </div>
                </div>

                <form wire:submit="verify" class="flex flex-col gap-5">
                    @if (! $usingRecovery)
                        <flux:otp wire:model="code" length="6" label="Authenticator Code" label:sr-only
                            :error:icon="false" error:class="text-center" class="mx-auto" autofocus />
                    @else
                        <flux:field>
                            <flux:label>Recovery Code</flux:label>
                            <flux:input wire:model="code" type="text" placeholder="XXXXX-XXXXX"
                                autocomplete="one-time-code" class="font-mono tracking-widest" autofocus />
                            <flux:error name="code" />
                        </flux:field>
                    @endif

                    <flux:button type="submit" variant="primary" class="w-full">
                        <span wire:loading.remove>{{ $usingRecovery ? 'Use recovery code' : 'Verify code' }}</span>
                        <span wire:loading>Verifying…</span>
                    </flux:button>
                </form>

                <div class="border-t border-white/6 pt-4 text-center">
                    <button type="button" wire:click="toggleRecovery"
                        class="text-sm font-medium text-blue-400 transition hover:text-blue-300">
                        {{ $usingRecovery ? 'Use authenticator app instead' : 'Use a recovery code instead' }}
                    </button>
                </div>

            </flux:card>

        </div>
    </div>

</div>
