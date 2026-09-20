<?php

namespace App\Support;

use App\Contracts\Languages;
use App\Models\AppSetting;
use Illuminate\Http\Request;

class PortalResolver
{
    public function __construct(private Languages $languages) {}

    /**
     * Resolve which portal a request belongs to.
     *
     * Multi-domain mode: exact Host header match against
     * config('multidomain.sub_domains') — reliable even on a 404, since the
     * Host header is always present.
     *
     * Single-domain mode: every portal shares one host, so the first URI
     * segment is used instead. Landing and auth both keep unprefixed paths
     * (see routes/web.php), so an unmatched segment falls back to "landing".
     */
    public function resolve(Request $request): string
    {
        return config('multidomain.single_domain')
            ? $this->resolveBySegment($request)
            : $this->resolveByHost($request);
    }

    private function resolveByHost(Request $request): string
    {
        $host = $request->getHost();

        foreach (config('multidomain.sub_domains', []) as $portal => $domain) {
            if ($domain === $host) {
                return $portal;
            }
        }

        return 'landing';
    }

    private function resolveBySegment(Request $request): string
    {
        // routes/web.php puts the locale segment first when both apply
        // (example.com/ar/app/dashboard), so the portal segment shifts to
        // position 2 whenever the first segment is a locale, not a portal —
        // only possible when URL mode is "path" (see AppSetting::isPathMode()).
        $offset = AppSetting::isPathMode() && in_array($request->segment(1), $this->languages->activeCodes(), true)
            ? 2
            : 1;

        $segment = $request->segment($offset);

        if ($segment && array_key_exists($segment, config('multidomain.sub_domains', []))) {
            return $segment;
        }

        return 'landing';
    }
}
