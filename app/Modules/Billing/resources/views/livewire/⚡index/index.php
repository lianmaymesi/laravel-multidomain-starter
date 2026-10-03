<?php

use App\Modules\Billing\Services\Plans;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Laravel\Cashier\Subscription;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Backoffice → Billing. Everyone with billing.view sees every subscription
 * and its state; billing.manage (Super Admin) can also cancel or resume one.
 * Each staff action lands on the customer's activity timeline under
 * "billing", with who did it.
 */
new #[Layout('layouts.backoffice')] class extends Component
{
    use WithPagination;

    public const STATUSES = ['active', 'trialing', 'grace', 'past_due', 'incomplete', 'ended'];

    #[Url]
    public string $status = '';

    public string $search = '';

    public function mount(): void
    {
        abort_unless(Gate::allows('billing.view'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function ready(): bool
    {
        return app(Plans::class)->ready();
    }

    /** @return array<string, int> */
    public function stats(): array
    {
        return [
            'active' => $this->base()->active()->notOnTrial()->notOnGracePeriod()->count(),
            'trialing' => $this->base()->onTrial()->count(),
            'grace' => $this->base()->onGracePeriod()->count(),
            'past_due' => $this->base()->pastDue()->count(),
        ];
    }

    public function subscriptions(): LengthAwarePaginator
    {
        $search = trim($this->search);

        return $this->base()
            ->with('owner')
            ->when($search !== '', fn (Builder $query) => $query->whereHas('owner', fn (Builder $owner) => $owner
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when(in_array($this->status, self::STATUSES, true), fn (Builder $query) => match ($this->status) {
                'active' => $query->active()->notOnTrial()->notOnGracePeriod(),
                'trialing' => $query->onTrial(),
                'grace' => $query->onGracePeriod(),
                'past_due' => $query->pastDue(),
                'incomplete' => $query->incomplete(),
                'ended' => $query->ended(),
            })
            ->latest()
            ->paginate(15);
    }

    /** @return array{0: string, 1: string} label and badge color */
    public function statusOf(Subscription $subscription): array
    {
        return match (true) {
            $subscription->ended() => [__('Ended'), 'zinc'],
            $subscription->hasIncompletePayment() => [__('Incomplete'), 'amber'],
            $subscription->pastDue() => [__('Past due'), 'red'],
            $subscription->onGracePeriod() => [__('Cancelling'), 'orange'],
            $subscription->onTrial() => [__('Trial'), 'blue'],
            $subscription->active() => [__('Active'), 'emerald'],
            default => [str($subscription->stripe_status)->headline()->toString(), 'zinc'],
        };
    }

    public function planName(Subscription $subscription): string
    {
        return app(Plans::class)->nameForPrice($subscription->stripe_price);
    }

    /** At period end: the customer keeps what they paid for. */
    public function cancel(int $id): void
    {
        $subscription = $this->manageable($id);

        abort_if($subscription->canceled(), 404);

        $subscription->cancel();

        $this->audit($subscription, 'subscription cancelled by staff');
        session()->flash('status', __('Subscription cancelled at the end of its billing period.'));
    }

    public function cancelNow(int $id): void
    {
        $subscription = $this->manageable($id);

        abort_if($subscription->ended(), 404);

        $subscription->cancelNow();

        $this->audit($subscription, 'subscription ended by staff');
        session()->flash('status', __('Subscription ended immediately.'));
    }

    public function resume(int $id): void
    {
        $subscription = $this->manageable($id);

        abort_unless($subscription->onGracePeriod(), 404);

        $subscription->resume();

        $this->audit($subscription, 'subscription resumed by staff');
        session()->flash('status', __('Subscription resumed.'));
    }

    private function base(): Builder
    {
        return Subscription::query()->where('type', config('billing.subscription'));
    }

    private function manageable(int $id): Subscription
    {
        abort_unless(Gate::allows('billing.manage'), 403);

        return $this->base()->with('owner')->findOrFail($id);
    }

    private function audit(Subscription $subscription, string $description): void
    {
        activity('billing')
            ->causedBy(auth()->user())
            ->performedOn($subscription->owner)
            ->withProperties(['plan' => $this->planName($subscription), 'subscription' => $subscription->stripe_id])
            ->log($description);
    }
};
