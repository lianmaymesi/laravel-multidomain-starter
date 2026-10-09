<?php

namespace Atrium\Core\Support\Health\Checks;

use Atrium\Core\Support\Health\Check;
use Atrium\Core\Support\Health\Result;

/** Settings that are fine locally but dangerous or slow in production. */
class EnvironmentCheck implements Check
{
    public function name(): string
    {
        return 'environment';
    }

    public function label(): string
    {
        return 'Environment';
    }

    public function run(): Result
    {
        $environment = app()->environment();
        $meta = ['environment' => $environment];

        if (blank(config('app.key'))) {
            return Result::failed('APP_KEY is not set — run `php artisan key:generate`.', $meta);
        }

        if (! app()->isProduction()) {
            return Result::ok("Running in \"{$environment}\".", $meta);
        }

        if (config('app.debug')) {
            return Result::failed('APP_DEBUG is on in production — error pages expose secrets.', $meta);
        }

        $warnings = [];

        if (! app()->configurationIsCached()) {
            $warnings[] = 'config not cached (`php artisan config:cache`)';
        }

        if (! app()->routesAreCached()) {
            $warnings[] = 'routes not cached (`php artisan route:cache`)';
        }

        if (! str_starts_with((string) config('app.url'), 'https://')) {
            $warnings[] = 'APP_URL is not https';
        }

        return $warnings === []
            ? Result::ok('Production settings look right.', $meta)
            : Result::warning(ucfirst(implode('; ', $warnings)).'.', $meta);
    }
}
