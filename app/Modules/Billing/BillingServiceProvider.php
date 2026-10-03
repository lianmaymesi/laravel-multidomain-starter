<?php

namespace App\Modules\Billing;

use App\Events\AccountDeleting;
use App\Modules\Billing\Health\BillingCheck;
use App\Modules\Billing\Listeners\CancelSubscriptionsOnAccountDeletion;
use App\Modules\Billing\Listeners\LogSubscriptionWebhook;
use App\Modules\Billing\Services\Plans;
use App\Support\Modules\Module;
use App\Support\Modules\ModuleProvider;
use Illuminate\Support\Facades\Event;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Events\WebhookHandled;
use Livewire\Livewire;

class BillingServiceProvider extends ModuleProvider
{
    protected function module(): string
    {
        return 'billing';
    }

    protected function label(): string
    {
        return 'Billing';
    }

    protected function description(): string
    {
        return 'Stripe subscriptions: plans, checkout, billing portal and webhooks.';
    }

    protected function icon(): string
    {
        return 'credit-card';
    }

    protected function permissions(): array
    {
        return ['billing.view', 'billing.manage'];
    }

    /** Cancelling or resuming someone's subscription moves real money. */
    protected function superAdminOnlyPermissions(): array
    {
        return ['billing.manage'];
    }

    protected function registerModule(): void
    {
        // Our routes/stripe.php registers Cashier's routes instead, so they
        // follow the module toggle.
        Cashier::ignoreRoutes();

        $this->app->singleton(Plans::class);
    }

    protected function registerDisabled(): void
    {
        // Cashier is a normal package and would register /stripe/webhook on
        // its own — a disabled module must leave no routes behind.
        Cashier::ignoreRoutes();
    }

    protected function bootModule(): void
    {
        $this->loadRoutesFrom($this->modulePath('routes/stripe.php'));

        Livewire::addNamespace('billing', viewPath: $this->modulePath('resources/views/livewire'));

        Event::listen(AccountDeleting::class, CancelSubscriptionsOnAccountDeletion::class);
        Event::listen(WebhookHandled::class, LogSubscriptionWebhook::class);

        Module::contribute('account.nav', [[
            'label' => 'Billing',
            'route' => 'account.billing',
            'icon' => 'credit-card',
            'order' => 45,
        ]]);

        Module::contribute('backoffice.nav', [[
            'label' => 'Billing',
            'route' => 'backoffice.billing.index',
            'icon' => 'credit-card',
            'permission' => 'billing.view',
            'order' => 30,
        ]]);

        Module::contribute('health.checks', [BillingCheck::class]);
    }
}
