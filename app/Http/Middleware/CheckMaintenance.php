<?php

namespace App\Http\Middleware;

use App\Models\PortalSetting;
use App\Support\PortalResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenance
{
    public function __construct(private PortalResolver $portals) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (config('maintenance.global')) {
            return $this->maintenanceResponse(null);
        }

        $portal = $this->portals->resolve($request);

        if (in_array($portal, config('maintenance.exempt_portals', []), true)) {
            return $next($request);
        }

        if (PortalSetting::isDown($portal)) {
            return $this->maintenanceResponse(PortalSetting::messageFor($portal));
        }

        return $next($request);
    }

    private function maintenanceResponse(?string $message): Response
    {
        return response()
            ->view('errors._dispatch', ['code' => 'maintenance', 'message' => $message], 503)
            ->header('Retry-After', 3600);
    }
}
