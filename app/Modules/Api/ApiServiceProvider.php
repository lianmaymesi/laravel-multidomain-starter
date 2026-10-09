<?php

namespace App\Modules\Api;

use App\Models\User;
use App\Modules\Api\Http\Middleware\EnsureApiAccess;
use App\Modules\Api\Http\Middleware\ForceJsonResponse;
use App\Modules\Api\Models\PersonalAccessToken;
use App\Modules\Api\Services\ApiAccess;
use App\Modules\Api\Support\Api;
use Atrium\Core\Support\Modules\Module;
use Atrium\Core\Support\Modules\ModuleProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

/**
 * The JSON API (Sanctum tokens) on the api subdomain. Off by default — not
 * every app needs one. Who may hold tokens is decided at runtime on
 * Backoffice → API Access; other modules add endpoints with routes/api.php
 * and abilities with Module::contribute('api.abilities', …).
 */
class ApiServiceProvider extends ModuleProvider
{
    protected function module(): string
    {
        return 'api';
    }

    protected function label(): string
    {
        return 'API';
    }

    protected function description(): string
    {
        return 'Token-authenticated JSON API on the api subdomain, with a Super Admin access policy.';
    }

    protected function icon(): string
    {
        return 'code-bracket';
    }

    protected function permissions(): array
    {
        return ['api.manage'];
    }

    protected function superAdminOnlyPermissions(): array
    {
        return ['api.manage'];
    }

    protected function registerModule(): void
    {
        $this->app->singleton(ApiAccess::class);

        // Adds issued_by + issuer() to Sanctum's tokens.
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }

    protected function bootModule(): void
    {
        Livewire::addNamespace('api', viewPath: $this->modulePath('resources/views/livewire'));

        $router = $this->app['router'];
        $router->aliasMiddleware('abilities', CheckAbilities::class); // needs all listed
        $router->aliasMiddleware('ability', CheckForAnyAbility::class); // needs any listed

        RateLimiter::for('api', fn (Request $request) => Limit::perMinute((int) config('api.rate_limit', 60))
            ->by($request->user()?->getAuthIdentifier() ?? $request->ip()));

        // Errors on the API host are JSON — even for routes that don't exist.
        Module::contribute('http.json-requests', [fn (Request $request) => Api::is($request)]);

        Module::contribute('backoffice.nav', [[
            'label' => 'API Access',
            'route' => 'backoffice.api.index',
            'icon' => 'code-bracket',
            'permission' => 'api.manage',
            'order' => 90,
        ]]);

        // Only for users the access policy lets create tokens (or who hold
        // one an admin issued) — otherwise the page doesn't exist for them.
        Module::contribute('account.nav', [[
            'label' => 'API tokens',
            'route' => 'account.api-tokens',
            'icon' => 'key',
            'order' => 60,
            'visible' => fn (?User $user) => $user !== null
                && (app(ApiAccess::class)->canSelfServe($user) || $user->tokens()->exists()),
        ]]);

        if (! $this->app->routesAreCached()) {
            $this->registerApiRoutes();
        }
    }

    /**
     * /v1 on the api subdomain (/api/v1 in single-domain mode). Every module's
     * routes/api.php — this one's included — is loaded inside the
     * authenticated group, so a disabled module's endpoints don't exist.
     */
    private function registerApiRoutes(): void
    {
        Route::domain(config('multidomain.sub_domains.api'))
            ->prefix(Api::prefix())
            ->middleware(['api', ForceJsonResponse::class, 'throttle:api'])
            ->name('api.'.config('api.version').'.')
            ->group(function () {
                Route::get('/', fn () => [
                    'name' => config('app.name'),
                    'version' => config('api.version'),
                ])->name('index');

                Route::middleware(['auth:sanctum', EnsureApiAccess::class])->group(fn () => Module::routes('api'));
            });
    }
}
