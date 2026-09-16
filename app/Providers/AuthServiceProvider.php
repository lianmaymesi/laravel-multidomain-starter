<?php

namespace App\Providers;

use App\Http\Middleware\Authenticate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Override the framework's Authenticate middleware with our custom implementation
        $this->app->bind(\Illuminate\Auth\Middleware\Authenticate::class, Authenticate::class);

        // Super Admin bypasses every ability check, current and future —
        // returning null for everyone else falls through to Spatie's own
        // Gate::before, which resolves e.g. Gate::allows('roles.view') to
        // hasPermissionTo('roles.view').
        Gate::before(fn (User $user, string $ability) => $user->hasRoleSlug(Role::SUPER_ADMIN) ? true : null);
    }
}
