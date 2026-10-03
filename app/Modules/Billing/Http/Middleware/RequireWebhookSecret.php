<?php

namespace App\Modules\Billing\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cashier only verifies the Stripe-Signature header when a webhook secret is
 * configured — without one it would trust any POST. This refuses instead.
 * 503 (not 4xx) so Stripe keeps retrying until the secret is set, and no
 * event is lost in the meantime.
 */
class RequireWebhookSecret
{
    public function handle(Request $request, Closure $next): Response
    {
        if (blank(config('cashier.webhook.secret'))) {
            Log::warning('Stripe webhook refused: STRIPE_WEBHOOK_SECRET is not set.');

            return response('Webhook secret not configured.', 503);
        }

        return $next($request);
    }
}
