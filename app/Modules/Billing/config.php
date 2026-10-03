<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Subscription Type
    |--------------------------------------------------------------------------
    |
    | Cashier's subscription "type" — the name every user's subscription is
    | stored under. One type means a user has at most one plan at a time.
    |
    */

    'subscription' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Plans
    |--------------------------------------------------------------------------
    |
    | What the Account → Billing page offers. Stripe is the source of truth for
    | prices: `price` is a Stripe Price id (price_...), and `amount` is only a
    | display label. A plan with no price id is hidden, so unused plans can
    | stay here. Rename, add or remove plans freely — the key is internal.
    |
    | Letting customers switch plans is done in the Stripe Billing Portal:
    | add these prices to its product catalogue in the Stripe dashboard.
    |
    */

    'plans' => [
        'starter' => [
            'name' => 'Starter',
            'description' => 'For individuals getting started.',
            'price' => env('BILLING_STARTER_PRICE'),
            'amount' => env('BILLING_STARTER_AMOUNT', '$9 / month'),
            'features' => ['Everything in the app', 'Email support'],
        ],
        'pro' => [
            'name' => 'Pro',
            'description' => 'For professionals who need more.',
            'price' => env('BILLING_PRO_PRICE'),
            'amount' => env('BILLING_PRO_AMOUNT', '$29 / month'),
            'features' => ['Everything in Starter', 'Priority support'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Free Trial
    |--------------------------------------------------------------------------
    |
    | Days of free trial on a new subscription (0 = none). Stripe collects
    | the card up front through Checkout and charges when the trial ends.
    |
    */

    'trial_days' => (int) env('BILLING_TRIAL_DAYS', 0),

];
