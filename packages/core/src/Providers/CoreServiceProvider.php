<?php

namespace Atrium\Core\Providers;

use Atrium\Core\Console\Commands\HealthCheckCommand;
use Atrium\Core\Console\Commands\MakeFlagCommand;
use Atrium\Core\Console\Commands\MakeModuleCommand;
use Atrium\Core\Console\Commands\MakeSubdomainCommand;
use Atrium\Core\Console\Commands\SetupCommand;
use Atrium\Core\Contracts\Currencies;
use Atrium\Core\Contracts\Languages;
use Atrium\Core\Contracts\SmsService;
use Atrium\Core\Models\User;
use Atrium\Core\Services\Auth\TwilioSmsService;
use Atrium\Core\Services\TimezoneService;
use Atrium\Core\Support\NullCurrencies;
use Atrium\Core\Support\NullLanguages;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

/**
 * atrium-php/core's own services and defaults. Registered through package
 * discovery before the project's providers, so a project overrides any
 * binding here from its AppServiceProvider.
 */
class CoreServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Twilio is Atrium's default SmsService — rebind the contract in a
        // project provider to use a different SMS gateway.
        $this->app->bind(SmsService::class, TwilioSmsService::class);

        // Core money formatting works without the Currency module; when it is
        // enabled its provider binds the database-backed service.
        $this->app->bindIf(Currencies::class, NullCurrencies::class);

        // Same idea for languages: one locale, no prefix, LTR until the Language
        // module is enabled and binds its own.
        $this->app->bindIf(Languages::class, NullLanguages::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                HealthCheckCommand::class,
                MakeFlagCommand::class,
                MakeModuleCommand::class,
                MakeSubdomainCommand::class,
                SetupCommand::class,
            ]);
        }

        $this->registerRateLimiters();

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

    /**
     * Named limiters for plain HTTP routes (`throttle:<name>`), from
     * config/rate-limits.php. Livewire actions are limited in the component
     * instead — see Atrium\Core\Concerns\ThrottlesActions.
     */
    private function registerRateLimiters(): void
    {
        RateLimiter::for('downloads', fn (Request $request) => Limit::perSecond(
            (int) config('rate-limits.downloads.max'), (int) config('rate-limits.downloads.decay'),
        )->by($request->user()?->getAuthIdentifier() ?? $request->ip()));

        RateLimiter::for('links', fn (Request $request) => Limit::perSecond(
            (int) config('rate-limits.links.max'), (int) config('rate-limits.links.decay'),
        )->by($request->ip()));
    }
}
