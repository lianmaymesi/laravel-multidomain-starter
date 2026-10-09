<?php

namespace Atrium\Core\Support\Health\Checks;

use Atrium\Core\Support\Health\Check;
use Atrium\Core\Support\Health\Result;
use Illuminate\Support\Facades\Route;

/**
 * Multidomain wiring: every portal in config/multidomain.php has a host, no
 * two portals share one (unless single-domain mode), and each portal's entry
 * route is registered — so a typo in a domain or a missing route file shows
 * up here instead of as a 404 for users.
 */
class PortalsCheck implements Check
{
    /** Entry route per built-in portal; other portals (make:subdomain) use "{portal}.dashboard". */
    private const ENTRY_ROUTES = [
        'landing' => 'index',
        'auth' => 'auth.login',
        'app' => 'app.dashboard',
        'account' => 'account.index',
        'backoffice' => 'backoffice.dashboard',
    ];

    /** Portals reserved in config that ship without routes. */
    private const OPTIONAL = ['api'];

    public function name(): string
    {
        return 'portals';
    }

    public function label(): string
    {
        return 'Portals';
    }

    public function run(): Result
    {
        $portals = config('multidomain.sub_domains', []);
        $single = (bool) config('multidomain.single_domain');
        $problems = [];

        if (blank(config('multidomain.main_domain'))) {
            $problems[] = 'APP_MAIN_DOMAIN is not set';
        }

        foreach ($portals as $portal => $host) {
            if (blank($host) || str_starts_with((string) $host, '.') || str_ends_with((string) $host, '.')) {
                $problems[] = "\"{$portal}\" has no valid host";

                continue;
            }

            $route = self::ENTRY_ROUTES[$portal] ?? "{$portal}.dashboard";

            if (! in_array($portal, self::OPTIONAL, true) && ! Route::has($route)) {
                $problems[] = "\"{$portal}\" has no {$route} route";
            }
        }

        if (! $single) {
            $duplicates = array_keys(array_filter(array_count_values(array_map('strval', $portals)), fn (int $count) => $count > 1));

            foreach ($duplicates as $host) {
                $problems[] = "several portals share {$host}";
            }
        }

        $meta = ['mode' => $single ? 'single-domain' : 'multi-domain', 'portals' => $portals];

        return $problems === []
            ? Result::ok(count($portals).' portals wired ('.$meta['mode'].').', $meta)
            : Result::failed(ucfirst(implode('; ', $problems)).'.', $meta);
    }
}
