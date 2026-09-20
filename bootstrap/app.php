<?php

use App\Http\Middleware\Demo\EnsureEmailVerificationNotExpired;
use App\Http\Middleware\Demo\EnsureIsStaff;
use App\Http\Middleware\Demo\EnsurePhoneIsVerified;
use App\Http\Middleware\EnsurePortalAccess;
use App\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->alias([
            // Core — the reusable multidomain/portal mechanism
            'guest' => RedirectIfAuthenticated::class,
            'portal' => EnsurePortalAccess::class,

            // Demo/example — this app's own auth flow (phone OTP, email grace
            // period, staff role). Replace or remove for your own app.
            'phone.verified' => EnsurePhoneIsVerified::class,
            'email.grace' => EnsureEmailVerificationNotExpired::class,
            'staff' => EnsureIsStaff::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Route every HTTP error (404, 419, 500, ...) through one dynamic
        // view instead of Laravel's per-code errors::{code} convention —
        // the framework ships its own default view for several common
        // codes, which would otherwise pre-empt ours. See:
        // resources/views/errors/_dispatch.blade.php
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return response()->view('errors._dispatch', ['code' => $e->getStatusCode()], $e->getStatusCode(), $e->getHeaders());
        });
    })->create();
