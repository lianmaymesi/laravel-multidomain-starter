<?php

namespace App\Http\Middleware\Demo;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmailVerificationNotExpired
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

        // Already verified — proceed
        if ($user->hasVerifiedEmail()) {
            return $next($request);
        }

        // Grace period still active — let through but frontend should show banner
        if (! $user->emailVerificationExpired()) {
            return $next($request);
        }

        // Deadline passed — hard block, redirect to verify email
        return redirect()
            ->route('auth.verify-email')
            ->with('warning', __('Your email verification deadline has passed. Please verify your email to continue.'));
    }
}
