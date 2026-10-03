@php
    $title = __('API Access');
    $stats = $this->stats();
@endphp

<div class="space-y-8">

    <div>
        <flux:heading size="xl">{{ __('API Access') }}</flux:heading>
        <flux:text class="mt-1 text-zinc-500 dark:text-white/50">
            {{ __('Decide who may use the API, issue tokens for integrations, and see or revoke every token.') }}
            <span class="block text-xs">{{ __('API base URL:') }} <code>{{ $this->apiBaseUrl() }}</code></span>
        </flux:text>
    </div>

    @if ($status)
    <div class="border border-emerald-500/20 bg-emerald-500/6 px-4 py-3 text-sm text-emerald-500" data-status>{{ $status }}</div>
    @endif

    <div class="grid grid-cols-3 gap-4">
        <flux:card><flux:text class="text-xs uppercase tracking-wider">{{ __('Tokens') }}</flux:text><flux:heading size="xl">{{ $stats['total'] }}</flux:heading></flux:card>
        <flux:card><flux:text class="text-xs uppercase tracking-wider">{{ __('Used in 30 days') }}</flux:text><flux:heading size="xl">{{ $stats['used'] }}</flux:heading></flux:card>
        <flux:card><flux:text class="text-xs uppercase tracking-wider">{{ __('Issued by admins') }}</flux:text><flux:heading size="xl">{{ $stats['admin_issued'] }}</flux:heading></flux:card>
    </div>

    {{-- 1 · Access policy --}}
    <flux:card class="space-y-5">
        <div>
            <flux:heading size="lg">{{ __('Who can create their own tokens') }}</flux:heading>
            <flux:text class="text-zinc-500 dark:text-white/50">
                {{ __('Controls the API tokens page in each user\'s Account. Users outside this policy never see it, and tokens they created themselves stop working as soon as they lose access. Tokens you issue below are not affected.') }}
            </flux:text>
        </div>

        <form wire:submit="savePolicy" class="space-y-5">
            <flux:radio.group wire:model.live="mode" :label="__('Self-service')">
                <flux:radio value="none" :label="__('Nobody')" :description="__('Only administrators issue tokens. Users never see an API tokens page.')" />
                <flux:radio value="staff" :label="__('Backoffice staff')" :description="__('Staff accounts can create their own tokens.')" />
                <flux:radio value="everyone" :label="__('Everyone')" :description="__('Every signed-in user, in every portal.')" />
                <flux:radio value="roles" :label="__('Specific roles')" :description="__('Only users with one of the roles below.')" />
            </flux:radio.group>

            @if ($mode === 'roles')
            <flux:checkbox.group wire:model.live="roles" :label="__('Roles')">
                @foreach ($this->roleOptions() as $role)
                <flux:checkbox :value="$role->slug" :label="$role->name" />
                @endforeach
            </flux:checkbox.group>
            @endif

            <flux:text class="text-sm" data-eligible>
                {{ trans_choice('{0} No user can create tokens.|{1} :count user can create tokens.|[2,*] :count users can create tokens.', $this->eligibleCount()) }}
            </flux:text>

            @if ($mode !== 'none')
            <flux:checkbox.group wire:model="policyAbilities" :label="__('Abilities they may choose')">
                @foreach ($this->abilities() as $ability => $description)
                <flux:checkbox :value="$ability" :label="$ability" :description="__($description)" />
                @endforeach
            </flux:checkbox.group>

            <div class="flex flex-wrap gap-4">
                <flux:select wire:model="maxDays" :label="__('Longest lifetime')" class="max-w-48">
                    @foreach ($this->expiryOptions() as $value => $label)
                    <flux:select.option :value="$value">{{ $value === '' ? __('No limit') : $label }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input type="number" min="1" max="100" wire:model="maxTokens" :label="__('Tokens per user')" class="max-w-36" />
            </div>
            @endif

            <flux:button type="submit" variant="primary" size="sm">{{ __('Save policy') }}</flux:button>
        </form>
    </flux:card>

    {{-- 2 · Issue a token --}}
    <flux:card class="space-y-5">
        <div>
            <flux:heading size="lg">{{ __('Issue a token') }}</flux:heading>
            <flux:text class="text-zinc-500 dark:text-white/50">
                {{ __('For integrations, partners or service accounts — any user, any abilities, regardless of the policy above. The token can still never do more than that user\'s permissions allow.') }}
            </flux:text>
        </div>

        @if ($issuedToken)
        <div class="space-y-3 border border-amber-500/30 bg-amber-500/6 px-4 py-4" x-data="{ copied: false }" data-issued-token>
            <p class="text-sm font-medium text-amber-600 dark:text-amber-400">{{ __('Token for :email — copy it now, it won\'t be shown again.', ['email' => $issuedFor]) }}</p>
            <div class="flex gap-2">
                <input type="text" readonly x-ref="token" value="{{ $issuedToken }}" x-on:focus="$el.select()"
                    class="min-w-0 flex-1 border border-zinc-200 bg-white px-3 py-2 font-mono text-xs dark:border-white/10 dark:bg-white/5" />
                <flux:button size="sm" icon="clipboard" x-on:click="navigator.clipboard.writeText($refs.token.value); copied = true">
                    <span x-show="! copied">{{ __('Copy') }}</span><span x-show="copied" x-cloak>{{ __('Copied!') }}</span>
                </flux:button>
            </div>
            <flux:button size="sm" variant="ghost" wire:click="dismissIssued">{{ __('Done') }}</flux:button>
        </div>
        @endif

        <form wire:submit="issue" class="grid gap-4 md:grid-cols-2">
            <flux:input wire:model="issueEmail" type="email" :label="__('User email')" placeholder="integration@example.com" />
            <flux:input wire:model="issueName" :label="__('Token name')" :placeholder="__('e.g. ERP sync')" />

            <flux:checkbox.group wire:model="issueAbilities" :label="__('Abilities')" class="md:col-span-2">
                <flux:checkbox value="*" label="*" :description="__('Full access — everything the user is allowed to do')" />
                @foreach ($this->abilities() as $ability => $description)
                <flux:checkbox :value="$ability" :label="$ability" :description="__($description)" />
                @endforeach
            </flux:checkbox.group>

            <flux:select wire:model="issueExpiresIn" :label="__('Expires after')">
                @foreach ($this->expiryOptions() as $value => $label)
                <flux:select.option :value="$value">{{ $label }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex items-end">
                <flux:button type="submit" variant="primary" size="sm">{{ __('Issue token') }}</flux:button>
            </div>
        </form>
    </flux:card>

    {{-- 3 · All tokens --}}
    <flux:card class="space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <flux:heading size="lg">{{ __('All tokens') }}</flux:heading>
            <div class="flex flex-wrap items-center gap-2">
                <flux:input wire:model.live.debounce.300ms="search" size="sm" icon="magnifying-glass" :placeholder="__('Search user or token…')" class="w-64" />
                <flux:button size="sm" variant="danger" icon="no-symbol" wire:click="revokeAll"
                    wire:confirm="{{ __('Revoke EVERY API token, for every user? All integrations stop working until new tokens are issued.') }}">
                    {{ __('Revoke all') }}
                </flux:button>
            </div>
        </div>

        @php $tokens = $this->tokens(); @endphp
        <flux:table :paginate="$tokens">
            <flux:table.columns>
                <flux:table.column>{{ __('User') }}</flux:table.column>
                <flux:table.column>{{ __('Token') }}</flux:table.column>
                <flux:table.column>{{ __('Abilities') }}</flux:table.column>
                <flux:table.column>{{ __('Issued by') }}</flux:table.column>
                <flux:table.column>{{ __('Last used') }}</flux:table.column>
                <flux:table.column>{{ __('Expires') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>
            <flux:table.rows>
                @forelse ($tokens as $token)
                <flux:table.row wire:key="admin-token-{{ $token->id }}" data-admin-token="{{ $token->id }}">
                    <flux:table.cell>{{ $token->tokenable?->email ?? '—' }}</flux:table.cell>
                    <flux:table.cell class="font-medium">{{ $token->name }}</flux:table.cell>
                    <flux:table.cell class="text-xs">{{ implode(', ', $token->abilities) }}</flux:table.cell>
                    <flux:table.cell class="text-xs">
                        {{ ($token->issued_by === null || $token->issued_by === $token->tokenable_id) ? __('Self') : ($token->issuer?->name ?? __('Admin')) }}
                    </flux:table.cell>
                    <flux:table.cell class="text-xs">{{ $token->last_used_at?->diffForHumans() ?? __('Never') }}</flux:table.cell>
                    <flux:table.cell class="text-xs">{{ $token->expires_at ? ($token->expires_at->isPast() ? __('Expired') : $token->expires_at->forUser()->format('M j, Y')) : __('Never') }}</flux:table.cell>
                    <flux:table.cell align="end">
                        <flux:button size="sm" variant="ghost" icon="trash" wire:click="revoke({{ $token->id }})"
                            wire:confirm="{{ __('Revoke :name?', ['name' => $token->name]) }}" />
                    </flux:table.cell>
                </flux:table.row>
                @empty
                <flux:table.row>
                    <flux:table.cell colspan="7" class="text-center text-zinc-500">{{ __('No tokens.') }}</flux:table.cell>
                </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

</div>
