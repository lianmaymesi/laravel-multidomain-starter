<?php

namespace App\Http\Controllers;

use App\Support\Health\HealthChecker;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /health — readiness for uptime monitors and load balancers.
 *
 * Anyone gets the overall status; check details (versions, counts, hosts)
 * only with the HEALTH_TOKEN, sent as an X-Health-Token header or ?token=.
 * 200 while ok/warning, 503 once anything has failed. ?fresh=1 (with the
 * token) skips the short report cache.
 */
class HealthController extends Controller
{
    public function __invoke(Request $request, HealthChecker $health): JsonResponse
    {
        $authorized = $this->hasValidToken($request);
        $report = $health->report(fresh: $authorized && $request->boolean('fresh'));

        return response()
            ->json($authorized ? $report->toArray() : $report->summary(), $report->failed() ? 503 : 200)
            ->header('Cache-Control', 'no-store');
    }

    private function hasValidToken(Request $request): bool
    {
        $expected = (string) config('health.token');
        $given = (string) ($request->header('X-Health-Token') ?? $request->query('token', ''));

        return $expected !== '' && hash_equals($expected, $given);
    }
}
