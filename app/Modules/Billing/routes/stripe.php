<?php

use App\Modules\Billing\Http\Middleware\RequireWebhookSecret;
use Illuminate\Support\Facades\Route;
use Laravel\Cashier\Http\Controllers\PaymentController;
use Laravel\Cashier\Http\Controllers\WebhookController;

// Cashier's own routes, registered here instead of by Cashier so they only
// exist while the module is on. Same paths and names as Cashier's: no domain
// (any host) and no web middleware, so Stripe's POST needs no CSRF token.
// Cashier's controller verifies Stripe-Signature; RequireWebhookSecret makes
// sure there is a secret to verify against.
Route::prefix(config('cashier.path'))->name('cashier.')->group(function () {
    Route::get('payment/{id}', [PaymentController::class, 'show'])->name('payment');
    Route::post('webhook', [WebhookController::class, 'handleWebhook'])
        ->middleware(RequireWebhookSecret::class)
        ->name('webhook');
});
