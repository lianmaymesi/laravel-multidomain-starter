<?php

use App\Http\Middleware\Demo\EnsureEmailVerificationNotExpired;
use App\Http\Middleware\Demo\EnsureIsStaff;
use App\Http\Middleware\Demo\EnsurePhoneIsVerified;
use App\Http\Middleware\EnsurePortalAccess;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\RedirectIfAuthenticated;
use App\Support\Api;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        // The API lives on its own subdomain (/api/v1 in single-domain
        // mode), with JSON responses and its own rate limiter.
        then: function () {
            Route::domain(config('multidomain.sub_domains.api'))
                ->prefix(Api::prefix())
                ->middleware(['api', ForceJsonResponse::class, 'throttle:api'])
                ->name('api.'.config('api.version').'.')
                ->group(base_path('routes/api.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {

        $middleware->alias([
            // Core — the reusable multidomain/portal mechanism
            'guest' => RedirectIfAuthenticated::class,
            'portal' => EnsurePortalAccess::class,

            // API token abilities (Sanctum): `abilities:a,b` needs all,
            // `ability:a,b` needs any.
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,

            // Demo/example — this app's own auth flow (phone OTP, email grace
            // period, staff role). Replace or remove for your own app.
            'phone.verified' => EnsurePhoneIsVerified::class,
            'email.grace' => EnsureEmailVerificationNotExpired::class,
            'staff' => EnsureIsStaff::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Anything on the API host answers in JSON — including a 404 for a
        // route that doesn't exist, which never reaches ForceJsonResponse.
        $exceptions->shouldRenderJsonWhen(fn (Request $request) => Api::is($request) || $request->expectsJson());

        // Route every HTTP error (404, 419, 500, ...) through one dynamic
        // view instead of Laravel's per-code errors::{code} convention —
        // the framework ships its own default view for several common
        // codes, which would otherwise pre-empt ours. See:
        // resources/views/errors/_dispatch.blade.php
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->expectsJson() || Api::is($request)) {
                return null;
            }

            return response()->view('errors._dispatch', ['code' => $e->getStatusCode()], $e->getStatusCode(), $e->getHeaders());
        });
    })->create();
