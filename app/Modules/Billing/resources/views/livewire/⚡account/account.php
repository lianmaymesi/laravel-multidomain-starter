<?php

use App\Modules\Billing\Services\Plans;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Laravel\Cashier\Subscription;
use Livewire\Attributes\Layout;
use Livewire\Component;

/**
 * Account → Billing. Subscribing goes through Stripe Checkout and card,
 * invoice and plan changes through the Stripe Billing Portal, so card details
 * never touch this app. Stripe tells us the result by webhook, which is what
 * creates and updates the local subscription row.
 */
new #[Layout('layouts.accounts')] class extends Component
{
    public bool $returnedFromCheckout = false;

    public function mount(): void
    {
        $this->returnedFromCheckout = request()->query('checkout') === 'success';
    }

    public function ready(): bool
    {
        return app(Plans::class)->ready();
    }

    /** @return Collection<string, array<string, mixed>> */
    public function plans(): Collection
    {
        return app(Plans::class)->all();
    }

    /** The current subscription, or null when there is none or it has ended. */
    public function subscription(): ?Subscription
    {
        $subscription = Auth::user()->subscription(config('billing.subscription'));

        return $subscription && ! $subscription->ended() ? $subscription : null;
    }

    public function planName(Subscription $subscription): string
    {
        return app(Plans::class)->nameForPrice($subscription->stripe_price);
    }

    public function subscribe(string $plan): void
    {
        abort_unless($this->ready(), 503);

        $plan = app(Plans::class)->find($plan);

        abort_if($plan === null, 404);

        // Already subscribed: changing plan is done in the Billing Portal.
        if ($this->subscription() !== null) {
            return;
        }

        $checkout = Auth::user()
            ->newSubscription(config('billing.subscription'), $plan['price'])
            ->when(config('billing.trial_days') > 0, fn ($builder) => $builder->trialDays(config('billing.trial_days')))
            ->checkout([
                'success_url' => route('account.billing', ['checkout' => 'success']),
                'cancel_url' => route('account.billing'),
            ]);

        $this->redirect($checkout->url);
    }

    public function manage(): void
    {
        abort_unless($this->ready() && Auth::user()->hasStripeId(), 404);

        $this->redirect(Auth::user()->billingPortalUrl(route('account.billing')));
    }

    public function cancel(): void
    {
        $subscription = $this->subscription();

        abort_unless($subscription !== null && ! $subscription->canceled(), 404);

        $subscription->cancel();

        session()->flash('status', __('Your subscription is cancelled. You keep access until :date.', [
            'date' => $subscription->ends_at->forUser()->translatedFormat('F j, Y'),
        ]));
    }

    public function resume(): void
    {
        $subscription = $this->subscription();

        abort_unless($subscription?->onGracePeriod(), 404);

        $subscription->resume();

        session()->flash('status', __('Your subscription is active again.'));
    }
};
