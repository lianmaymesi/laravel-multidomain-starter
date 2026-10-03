@php
    $title = __('Billing');
    $stats = $this->stats();
    $subscriptions = $this->subscriptions();
@endphp

<div class="space-y-8">

    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <flux:heading size="xl">{{ __('Billing') }}</flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-white/50">{{ __('Every customer subscription, kept in sync by Stripe webhooks. Prices, refunds and invoices live in the Stripe dashboard.') }}</flux:text>
        </div>
        <div class="flex items-center gap-3">
            <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('Search customers…') }}" icon="magnifying-glass" class="max-w-xs" />
            <flux:select wire:model.live="status" class="max-w-40">
                <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
                <flux:select.option value="active">{{ __('Active') }}</flux:select.option>
                <flux:select.option value="trialing">{{ __('Trial') }}</flux:select.option>
                <flux:select.option value="grace">{{ __('Cancelling') }}</flux:select.option>
                <flux:select.option value="past_due">{{ __('Past due') }}</flux:select.option>
                <flux:select.option value="incomplete">{{ __('Incomplete') }}</flux:select.option>
                <flux:select.option value="ended">{{ __('Ended') }}</flux:select.option>
            </flux:select>
        </div>
    </div>

    @if (session('status'))
    <div class="border border-emerald-500/20 bg-emerald-500/6 px-4 py-3 text-sm text-emerald-400" data-status>
        {{ session('status') }}
    </div>
    @endif

    @unless ($this->ready())
    <div class="border border-amber-500/20 bg-amber-500/6 px-4 py-3 text-sm text-amber-500" data-not-ready>
        {!! __('Customers can\'t subscribe yet. Set :keys and at least one plan price (:price) in .env — see README "Billing".', ['keys' => '<code>STRIPE_KEY</code>, <code>STRIPE_SECRET</code>, <code>STRIPE_WEBHOOK_SECRET</code>', 'price' => '<code>BILLING_*_PRICE</code>']) !!}
    </div>
    @endunless

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            'active' => [__('Active'), 'emerald'],
            'trialing' => [__('On trial'), 'blue'],
            'grace' => [__('Cancelling'), 'orange'],
            'past_due' => [__('Past due'), 'red'],
        ] as $key => [$label, $color])
        <flux:card class="space-y-1" data-stat="{{ $key }}">
            <flux:text class="text-xs text-zinc-500 dark:text-white/40">{{ $label }}</flux:text>
            <flux:heading size="xl">{{ $stats[$key] }}</flux:heading>
        </flux:card>
        @endforeach
    </div>

    <flux:card>
        <flux:table :paginate="$subscriptions">
            <flux:table.columns>
                <flux:table.column>{{ __('Customer') }}</flux:table.column>
                <flux:table.column>{{ __('Plan') }}</flux:table.column>
                <flux:table.column>{{ __('Status') }}</flux:table.column>
                <flux:table.column>{{ __('Started') }}</flux:table.column>
                <flux:table.column>{{ __('Trial / access ends') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse ($subscriptions as $subscription)
                @php [$statusLabel, $statusColor] = $this->statusOf($subscription); @endphp
                <flux:table.row wire:key="subscription-{{ $subscription->id }}" data-subscription="{{ $subscription->stripe_id }}">
                    <flux:table.cell>
                        <div class="font-medium text-zinc-900 dark:text-white">{{ $subscription->owner?->name ?? __('Deleted user') }}</div>
                        <div class="text-xs text-zinc-500 dark:text-white/40">{{ $subscription->owner?->email }}</div>
                    </flux:table.cell>
                    <flux:table.cell>{{ $this->planName($subscription) }}</flux:table.cell>
                    <flux:table.cell><flux:badge size="sm" :color="$statusColor">{{ $statusLabel }}</flux:badge></flux:table.cell>
                    <flux:table.cell class="text-zinc-500 dark:text-white/50">{{ $subscription->created_at->forUser()->translatedFormat('M j, Y') }}</flux:table.cell>
                    <flux:table.cell class="text-zinc-500 dark:text-white/50">
                        @if ($subscription->ends_at)
                        {{ $subscription->ends_at->forUser()->translatedFormat('M j, Y') }}
                        @elseif ($subscription->onTrial())
                        {{ __('Trial to :date', ['date' => $subscription->trial_ends_at->forUser()->translatedFormat('M j, Y')]) }}
                        @else
                        —
                        @endif
                    </flux:table.cell>
                    <flux:table.cell align="end">
                        <div class="flex justify-end gap-2">
                            @if ($subscription->owner)
                            @can('activity.view')
                            @module('activity')<x-dynamic-component component="activity::log-button" :model="$subscription->owner" />@endmodule
                            @endcan
                            @endif
                            @can('billing.manage')
                            @if ($subscription->onGracePeriod())
                            <flux:button size="xs" variant="ghost" icon="arrow-path" wire:click="resume({{ $subscription->id }})">{{ __('Resume') }}</flux:button>
                            @endif
                            @unless ($subscription->ended())
                            <flux:dropdown align="end">
                                <flux:button size="xs" variant="ghost" icon="ellipsis-horizontal" aria-label="{{ __('Actions') }}" />
                                <flux:menu>
                                    @unless ($subscription->canceled())
                                    <flux:menu.item wire:click="cancel({{ $subscription->id }})" wire:confirm="{{ __('Cancel at the end of the billing period? The customer keeps access until then.') }}">{{ __('Cancel at period end') }}</flux:menu.item>
                                    @endunless
                                    <flux:menu.item variant="danger" wire:click="cancelNow({{ $subscription->id }})" wire:confirm="{{ __('End this subscription now? Access stops immediately and nothing is refunded automatically.') }}">{{ __('End now') }}</flux:menu.item>
                                </flux:menu>
                            </flux:dropdown>
                            @endunless
                            @endcan
                        </div>
                    </flux:table.cell>
                </flux:table.row>
                @empty
                <flux:table.row>
                    <flux:table.cell colspan="6" class="text-center text-zinc-500 dark:text-white/40">{{ __('No subscriptions found.') }}</flux:table.cell>
                </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </flux:card>

</div>
