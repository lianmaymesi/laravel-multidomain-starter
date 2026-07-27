<?php

namespace App\Providers;

use App\Contracts\SmsService;
use App\Services\Auth\TwilioSmsService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Twilio is the starter kit's default SmsService — swap the bound
        // concrete class here to use a different provider.
        $this->app->bind(SmsService::class, TwilioSmsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(function () {
            return Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised();
        });
    }
}
