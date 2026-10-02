<?php

namespace App\Modules\Api\Http\Middleware;

use App\Modules\Api\Services\ApiAccess;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

/**
 * Re-checks the access policy on every request: a self-issued token stops
 * working the moment its owner loses self-service access (policy changed,
 * role removed). Admin-issued tokens are unaffected.
 */
class EnsureApiAccess
{
    public function __construct(private ApiAccess $access) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        // Persisted tokens only — Sanctum::actingAs() test doubles aren't.
        if ($token instanceof PersonalAccessToken && $token->exists && ! $this->access->allows($request->user(), $token)) {
            abort(403, __('API access is not enabled for your account.'));
        }

        return $next($request);
    }
}
