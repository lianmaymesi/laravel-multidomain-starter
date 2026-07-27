<?php

namespace App\Http\Middleware\Demo;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePhoneIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('auth.login');
        }

        if (!$user->hasVerifiedPhone()) {
            return redirect()->route('auth.verify-phone');
        }

        return $next($request);
    }
}
