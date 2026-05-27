<?php

namespace App\Providers;

use App\Http\Middleware\Authenticate;
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
    }
}
