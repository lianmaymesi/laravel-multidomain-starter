@php $title = __('API tokens'); @endphp

<div class="space-y-10">

    <section class="space-y-5">
        <div>
            <flux:heading size="lg" class="text-zinc-900! dark:text-white!">{{ __('API tokens') }}</flux:heading>
            <flux:text class="text-zinc-500! dark:text-white/40! text-sm!">
                {{ __('Tokens let scripts and apps use the API as you. A token can do at most what your account can — its abilities only narrow that down.') }}
            </flux:text>
            <flux:text class="mt-1 text-xs text-zinc-400 dark:text-white/35">
                {{ __('API base URL:') }} <code class="text-zinc-600 dark:text-white/60">{{ $this->apiBaseUrl() }}</code>
            </flux:text>
        </div>

        @if ($plainToken)
        <div class="rounded-[1.75rem] border border-amber-500/30 bg-amber-500/6 px-6 py-5 space-y-3" x-data="{ copied: false }" data-new-token>
            <p class="text-sm font-medium text-amber-600 dark:text-amber-400">{{ __('Copy your new token now — it won\'t be shown again.') }}</p>
            <div class="flex gap-2">
                <input type="text" readonly x-ref="token" value="{{ $plainToken }}" x-on:focus="$el.select()"
                    class="min-w-0 flex-1 rounded-lg border border-zinc-200 bg-white px-3 py-2 font-mono text-xs text-zinc-700 dark:border-white/10 dark:bg-white/5 dark:text-white/80" />
                <flux:button size="sm" icon="clipboard" x-on:click="navigator.clipboard.writeText($refs.token.value); copied = true">
                    <span x-show="! copied">{{ __('Copy') }}</span>
                    <span x-show="copied" x-cloak>{{ __('Copied!') }}</span>
                </flux:button>
            </div>
            <p class="text-xs text-zinc-500 dark:text-white/40">{{ __('Send it as a header:') }} <code>Authorization: Bearer &lt;token&gt;</code></p>
            <flux:button size="sm" variant="ghost" wire:click="dismissToken">{{ __('I\'ve copied it') }}</flux:button>
        </div>
        @endif

        @if ($this->canCreate())
        <form wire:submit="create" class="rounded-[1.75rem] border border-zinc-200 dark:border-white/[0.07] bg-zinc-50 dark:bg-white/3 px-6 py-5 space-y-5" data-create-form>
            <div class="flex items-center justify-between gap-4">
                <flux:heading size="sm">{{ __('Create a token') }}</flux:heading>
                <flux:text class="text-xs text-zinc-400 dark:text-white/35">{{ trans_choice(':count token left|:count tokens left', $this->remaining()) }}</flux:text>
            </div>

            <flux:input wire:model="name" :label="__('Name')" :placeholder="__('e.g. Mobile app, Zapier')" class="max-w-sm" />

            <flux:checkbox.group wire:model="abilities" :label="__('Abilities')">
                @foreach ($this->abilityOptions() as $ability => $description)
                <flux:checkbox :value="$ability" :label="$ability" :description="__($description)" />
                @endforeach
            </flux:checkbox.group>

            <flux:select wire:model="expiresIn" :label="__('Expires after')" class="max-w-48">
                @foreach ($this->expiryOptions() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="expiresIn" />

            <flux:button type="submit" variant="primary" size="sm" :disabled="$this->remaining() === 0">{{ __('Create token') }}</flux:button>
        </form>
        @else
        <div class="rounded-[1.75rem] border border-zinc-200 dark:border-white/[0.07] px-6 py-4 text-sm text-zinc-500 dark:text-white/40" data-self-service-off>
            {{ __('Creating tokens yourself isn\'t enabled for your account. The tokens below were issued to you by an administrator.') }}
        </div>
        @endif
    </section>

    <section class="space-y-4">
        <flux:heading size="sm">{{ __('Your tokens') }}</flux:heading>

        <div class="rounded-[1.75rem] border border-zinc-200 dark:border-white/[0.07] bg-zinc-50 dark:bg-white/3 divide-y divide-zinc-200 dark:divide-white/5">
            @forelse ($this->tokens() as $token)
            <div class="flex flex-wrap items-center justify-between gap-4 px-6 py-4" wire:key="token-{{ $token->id }}" data-token="{{ $token->id }}">
                <div class="min-w-0">
                    <p class="text-sm font-medium text-zinc-800 dark:text-white/80">
                        {{ $token->name }}
                        @if ($token->issued_by && $token->issued_by !== $token->tokenable_id)
                        <flux:badge size="sm" color="blue" class="ms-1">{{ __('Issued by :name', ['name' => $token->issuer?->name ?? __('an administrator')]) }}</flux:badge>
                        @endif
                    </p>
                    <p class="text-xs text-zinc-400 dark:text-white/35">
                        {{ implode(', ', $token->abilities) }}
                        · {{ $token->last_used_at ? __('last used :when', ['when' => $token->last_used_at->diffForHumans()]) : __('never used') }}
                        · @if ($token->expires_at)
                            {{ $token->expires_at->isPast() ? __('expired') : __('expires :when', ['when' => $token->expires_at->forUser()->format('M j, Y')]) }}
                          @else
                            {{ __('never expires') }}
                          @endif
                    </p>
                </div>
                <flux:button size="sm" variant="ghost" icon="trash" wire:click="revoke({{ $token->id }})"
                    wire:confirm="{{ __('Revoke :name? Anything using it stops working immediately.', ['name' => $token->name]) }}">
                    {{ __('Revoke') }}
                </flux:button>
            </div>
            @empty
            <p class="px-6 py-5 text-sm text-zinc-500 dark:text-white/40">{{ __('No tokens yet.') }}</p>
            @endforelse
        </div>
    </section>

</div>
