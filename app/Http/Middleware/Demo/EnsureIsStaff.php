<?php

namespace App\Http\Middleware\Demo;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsStaff
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('auth.login');
        }

        if (! $user->isStaff()) {
            // Redirect end-users to their own panel
            abort(403, __('You do not have permission to access this area.'));
        }

        return $next($request);
    }
}
