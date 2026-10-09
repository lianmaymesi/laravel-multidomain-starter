<?php

namespace App\Providers;

use App\Models\User;
use Atrium\Core\Platform;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services. Runs after Atrium's own providers,
     * so bindings here override Atrium's defaults (e.g. a different
     * Atrium\Core\Contracts\SmsService).
     */
    public function register(): void
    {
        Platform::useUserModel(User::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
