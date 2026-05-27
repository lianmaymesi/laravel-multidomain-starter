<?php

use App\Http\Middleware\EnsureEmailVerificationNotExpired;
use App\Http\Middleware\EnsureIsStaff;
use App\Http\Middleware\EnsurePhoneIsVerified;
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
            'guest' => RedirectIfAuthenticated::class,
            'phone.verified' => EnsurePhoneIsVerified::class,
            'email.grace' => EnsureEmailVerificationNotExpired::class,
            'staff' => EnsureIsStaff::class,
            'portal' => EnsurePortalAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
