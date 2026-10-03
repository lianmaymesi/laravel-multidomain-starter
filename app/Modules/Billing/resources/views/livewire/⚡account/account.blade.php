@php
    $title = __('Billing');
    $ready = $this->ready();
    $subscription = $this->subscription();
@endphp

<div class="space-y-10">

    <section class="space-y-5">

        <div class="flex items-start justify-between gap-4">
            <div>
                <flux:heading size="lg" class="text-zinc-900! dark:text-white!">{{ __('Billing') }}</flux:heading>
                <flux:text class="text-zinc-500! dark:text-white/40! text-sm!">{{ __('Your plan, payment method and invoices. Payments are handled securely by Stripe.') }}</flux:text>
            </div>
            @if ($ready && auth()->user()->hasStripeId())
            <flux:button wire:click="manage" variant="filled" size="sm" class="shrink-0" icon="arrow-top-right-on-square">
                {{ __('Payment & invoices') }}
            </flux:button>
            @endif
        </div>

        @if (session('status'))
        <div class="border border-emerald-500/20 bg-emerald-500/6 px-4 py-3 text-sm text-emerald-500" data-status>
            {{ session('status') }}
        </div>
        @endif

        @if (! $ready)
        <div class="rounded-2xl border border-zinc-200 dark:border-white/[0.07] bg-zinc-50 dark:bg-white/3 px-5 py-4 text-sm text-zinc-500 dark:text-white/50" data-not-ready>
            {{ __('Billing isn\'t available yet. Please check back later.') }}
        </div>

        @elseif ($subscription)
        @php
            [$statusLabel, $statusColor] = match (true) {
                $subscription->hasIncompletePayment() => [__('Payment needs confirmation'), 'amber'],
                $subscription->pastDue() => [__('Payment failed'), 'red'],
                $subscription->onGracePeriod() => [__('Cancelled'), 'zinc'],
                $subscription->onTrial() => [__('Trial'), 'blue'],
                default => [__('Active'), 'emerald'],
            };
        @endphp
        <div class="rounded-[1.75rem] border border-zinc-200 dark:border-white/[0.07] bg-zinc-50 dark:bg-white/3 px-6 py-5 space-y-4" data-subscription>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <flux:heading size="lg">{{ $this->planName($subscription) }}</flux:heading>
                    <flux:badge size="sm" :color="$statusColor">{{ $statusLabel }}</flux:badge>
                </div>

                @if ($subscription->onGracePeriod())
                <flux:button wire:click="resume" variant="primary" size="sm" icon="arrow-path">{{ __('Resume subscription') }}</flux:button>
                @elseif ($subscription->valid())
                <flux:button wire:click="cancel" wire:confirm="{{ __('Cancel your subscription? You keep access until the end of the current billing period.') }}" variant="ghost" size="sm">
                    {{ __('Cancel subscription') }}
                </flux:button>
                @endif
            </div>

            <flux:text class="text-sm text-zinc-500 dark:text-white/50">
                @if ($subscription->hasIncompletePayment() || $subscription->pastDue())
                {{ __('Your last payment didn\'t go through. Open "Payment & invoices" to update your card or confirm the payment.') }}
                @elseif ($subscription->onGracePeriod())
                {{ __('Access ends on :date. Resume any time before then to keep your plan.', ['date' => $subscription->ends_at->forUser()->translatedFormat('F j, Y')]) }}
                @elseif ($subscription->onTrial())
                {{ __('Your free trial ends on :date, then your card is charged automatically.', ['date' => $subscription->trial_ends_at->forUser()->translatedFormat('F j, Y')]) }}
                @else
                {{ __('Renews automatically. Change plan or card from "Payment & invoices".') }}
                @endif
            </flux:text>
        </div>

        @else

        @if ($returnedFromCheckout)
        <div class="flex items-center gap-3 rounded-2xl border border-blue-500/20 bg-blue-50 dark:bg-blue-500/10 px-5 py-4" data-checkout-pending>
            <flux:icon.arrow-path class="size-5 text-blue-600 dark:text-blue-400 shrink-0 animate-spin" />
            <div>
                <p class="text-sm font-medium text-blue-700 dark:text-blue-300">{{ __('Payment received') }}</p>
                <p class="text-xs text-blue-600 dark:text-blue-400/70 mt-0.5">{{ __('Your subscription appears here as soon as Stripe confirms it — usually within a few seconds.') }}</p>
            </div>
        </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            @foreach ($this->plans() as $plan)
            <div wire:key="plan-{{ $plan['key'] }}" class="flex flex-col rounded-[1.75rem] border border-zinc-200 dark:border-white/[0.07] bg-zinc-50 dark:bg-white/3 px-6 py-5">
                <flux:heading size="lg">{{ __($plan['name']) }}</flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-500 dark:text-white/40">{{ __($plan['description']) }}</flux:text>
                <p class="mt-4 text-2xl font-semibold text-zinc-900 dark:text-white">{{ $plan['amount'] }}</p>

                @if ($plan['features'] !== [])
                <ul class="mt-4 space-y-1.5">
                    @foreach ($plan['features'] as $feature)
                    <li class="flex items-center gap-2 text-sm text-zinc-600 dark:text-white/60">
                        <flux:icon.check class="size-4 text-emerald-500" />
                        {{ __($feature) }}
                    </li>
                    @endforeach
                </ul>
                @endif

                <div class="mt-6 flex-1 content-end">
                    <flux:button wire:click="subscribe('{{ $plan['key'] }}')" variant="primary" class="w-full">
                        <span wire:loading.remove wire:target="subscribe('{{ $plan['key'] }}')">
                            {{ config('billing.trial_days') > 0 ? __('Start :days-day free trial', ['days' => config('billing.trial_days')]) : __('Subscribe') }}
                        </span>
                        <span wire:loading wire:target="subscribe('{{ $plan['key'] }}')">{{ __('Redirecting…') }}</span>
                    </flux:button>
                </div>
            </div>
            @endforeach
        </div>

        @endif

    </section>

</div>
