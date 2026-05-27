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
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $portal): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('auth.login');
        }

        $shouldBeStaffPortal = $portal === 'staff';

        if ($user->isStaff() !== $shouldBeStaffPortal) {
            return redirect()->to($user->redirect());
        }

        return $next($request);
    }
}
