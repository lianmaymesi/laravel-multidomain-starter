<?php

namespace App\Modules\Api\Support;

use Illuminate\Http\Request;

class Api
{
    /** Path prefix: "v1", or "api/v1" in single-domain mode. */
    public static function prefix(): string
    {
        return (config('multidomain.single_domain') ? 'api/' : '').config('api.version', 'v1');
    }

    /**
     * Whether a request is for the API — true even when no route matched
     * (so a 404 on the API host is JSON, not the HTML error page).
     */
    public static function is(Request $request): bool
    {
        if (config('multidomain.single_domain')) {
            return $request->is('api', 'api/*');
        }

        return $request->getHost() === config('multidomain.sub_domains.api');
    }
}
