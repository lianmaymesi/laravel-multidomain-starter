<?php

use App\Http\Middleware\Demo\EnsureEmailVerificationNotExpired;
use App\Http\Middleware\Demo\EnsureIsStaff;
use App\Http\Middleware\Demo\EnsurePhoneIsVerified;
use App\Http\Middleware\EnsurePortalAccess;
use App\Http\Middleware\RedirectIfAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
        //
    })->create();
