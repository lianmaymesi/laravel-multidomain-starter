<?php

namespace App\Support\Health;

use App\Support\Modules\Module;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Runs every health check — core's (config/health.php) plus the ones enabled
 * modules contribute ('health.checks') — and keeps the last report briefly,
 * so a monitor polling /health can't turn health checks into load.
 */
class HealthChecker
{
    public const CACHE_KEY = 'health:report';

    public function __construct(private Container $container, private Cache $cache) {}

    /** @return array<int, Check> */
    public function checks(): array
    {
        return collect([...config('health.checks', []), ...Module::contributions('health.checks')])
            ->unique()
            ->map(fn (string $class) => $this->container->make($class))
            ->values()
            ->all();
    }

    /** The cached report when fresh enough, otherwise a new run. */
    public function report(bool $fresh = false): Report
    {
        $seconds = (int) config('health.cache_seconds', 30);

        if (! $fresh && $seconds > 0) {
            try {
                $cached = $this->cache->get(self::CACHE_KEY);

                if (is_array($cached)) {
                    return Report::fromArray($cached);
                }
            } catch (Throwable) {
                // The cache itself may be what's broken — just run the checks.
            }
        }

        $report = $this->run();

        try {
            if ($seconds > 0) {
                $this->cache->put(self::CACHE_KEY, $report->toArray(), $seconds);
            }
        } catch (Throwable) {
            // Reported by the cache check; never fail the health endpoint over it.
        }

        return $report;
    }

    public function run(): Report
    {
        $results = [];

        foreach ($this->checks() as $check) {
            $started = hrtime(true);

            try {
                $result = $check->run();
            } catch (Throwable $e) {
                $result = Result::failed($e::class.': '.$e->getMessage());
            }

            $result->durationMs = round((hrtime(true) - $started) / 1e6, 1);

            $results[$check->name()] = ['label' => $check->label(), 'result' => $result];
        }

        return new Report($results, Carbon::now());
    }
}
