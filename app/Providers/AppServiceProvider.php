<?php

namespace App\Providers;

use App\Contracts\Currencies;
use App\Contracts\Languages;
use App\Contracts\SmsService;
use App\Models\User;
use App\Services\Auth\TwilioSmsService;
use App\Services\TimezoneService;
use App\Support\NullCurrencies;
use App\Support\NullLanguages;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
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

        // Core money formatting works without the Currency module; when it is
        // enabled its provider rebinds this to the database-backed service.
        $this->app->bind(Currencies::class, NullCurrencies::class);

        // Same idea for languages: one locale, no prefix, LTR until the Language
        // module is enabled and rebinds this.
        $this->app->bind(Languages::class, NullLanguages::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The API's rate limit (config/api.php), per token's user or per IP.
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute((int) config('api.rate_limit', 60))
            ->by($request->user()?->getAuthIdentifier() ?? $request->ip()));
        Password::defaults(function () {
            return Password::min(8)
                ->letters()
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised();
        });

        // Converts a Carbon instance (stored/parsed in app.timezone = UTC)
        // to the given user's timezone preference for display — never
        // mutates config('app.timezone') itself, so storage/parsing is
        // unaffected everywhere else.
        Carbon::macro('forUser', function (?User $user = null) {
            /** @var Carbon $this */
            return $this->copy()->setTimezone(app(TimezoneService::class)->current($user));
        });
    }
}
