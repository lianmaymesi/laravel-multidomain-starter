<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalAccess
{
    /**
     * Handle an incoming request.
     *
     * `$portal` is either the literal "staff"/"user" (the legacy privilege
     * split) or a role name (e.g. "blog", for a subdomain scaffolded with
     * its own role via `make:subdomain`). Anything other than those two
     * literals is treated as a role check.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $portal): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('auth.login');
        }

        $hasAccess = match ($portal) {
            'staff' => $user->isStaff(),
            'user' => ! $user->isStaff(),
            default => $user->hasRole($portal),
        };

        if (! $hasAccess) {
            return redirect()->to($user->redirect());
        }

        return $next($request);
    }
}
