<?php

namespace App\Http\Middleware;

use App\Models\LocaleSetting;
use App\Services\LanguageService;
use App\Support\PortalResolver;
use Closure;
use Illuminate\Http\Request;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * These three run entirely off the account's Preferred Language once
     * signed in — routes/web.php never even registers a locale-prefixed
     * URL for them, but `?lang=` is a query string and would reach here
     * regardless of routing, so it still needs blocking explicitly.
     *
     * @var array<int, string>
     */
    private const ACCOUNT_PREFERENCE_ONLY_PORTALS = ['app', 'account', 'backoffice'];

    public function __construct(
        private LanguageService $languages,
        private PortalResolver $portals,
    ) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $active = $this->languages->active();

        // No languages configured yet (fresh install, before seeding) —
        // leave the app on config('app.locale') and skip mcamara entirely.
        if ($active->isEmpty()) {
            return $next($request);
        }

        LaravelLocalization::setSupportedLocales($this->languages->supportedLocalesArray());

        // One active language: multi-language UI is hidden everywhere, so
        // negotiation is pointless — force it and move on.
        if ($active->count() < 2) {
            LaravelLocalization::setLocale($active->first()->code);

            return $next($request);
        }

        $codes = $active->pluck('code')->all();
        $code = $this->resolve($request, $codes);

        LaravelLocalization::setLocale($code);
        $request->session()->put('locale', $code);

        return $next($request);
    }

    /**
     * @param  array<int, string>  $codes
     */
    private function resolve(Request $request, array $codes): string
    {
        // Session cookies are shared across every subdomain here, so
        // $request->user() can resolve on landing/auth too even though
        // neither requires login — a signed-in visitor casually browsing
        // the marketing site is still a visitor of that page, not of their
        // account. So the URL mechanism is checked first and applies to
        // everyone equally on landing/auth (where it's the only place it
        // ever runs — see below); the account preference is only forced
        // to the front on app/account/backoffice, where it's meant to be
        // the sole source of truth.
        $isAccountPreferencePortal = in_array($this->portals->resolve($request), self::ACCOUNT_PREFERENCE_ONLY_PORTALS, true);

        if ($isAccountPreferencePortal) {
            $user = $request->user();
            if ($user && $user->locale && in_array($user->locale, $codes, true)) {
                return $user->locale;
            }
        } else {
            // Only one URL-based mechanism is ever active (see backoffice
            // Languages page → URL mode) — checking both would let a
            // leftover `?lang=` survive a switch to path mode, or vice
            // versa. `?lang=`/the path segment never apply at all on
            // app/account/backoffice (see above) — this whole branch is
            // landing/auth only.
            if (LocaleSetting::isPathMode()) {
                // routes/web.php already registered this request's routes
                // under this same prefix, so honoring anything else here
                // would desync app()->getLocale() from the URL the visitor
                // is actually looking at.
                $pathLocale = $request->segment(1);
                if (is_string($pathLocale) && in_array($pathLocale, $codes, true)) {
                    return $pathLocale;
                }
            } else {
                $queryLocale = $request->query('lang');
                if (is_string($queryLocale) && in_array($queryLocale, $codes, true)) {
                    return $queryLocale;
                }
            }

            // No explicit URL signal — a signed-in visitor still gets their
            // saved preference as the default, just not locked to it; the
            // switcher/`?lang=` above always takes priority when present.
            $user = $request->user();
            if ($user && $user->locale && in_array($user->locale, $codes, true)) {
                return $user->locale;
            }
        }

        $sessionLocale = $request->session()->get('locale');
        if (is_string($sessionLocale) && in_array($sessionLocale, $codes, true)) {
            return $sessionLocale;
        }

        // Request::getPreferredLanguage() never actually returns null — with
        // no Accept-Language header at all it silently falls back to the
        // first of $codes, not the primary language. Checking getLanguages()
        // (the raw parsed header) first avoids that trap.
        if ($request->getLanguages() !== []) {
            $negotiated = $request->getPreferredLanguage($codes);
            if ($negotiated !== null) {
                return $negotiated;
            }
        }

        return $this->languages->primaryCode();
    }
}
