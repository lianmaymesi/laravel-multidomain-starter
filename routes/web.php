<?php

use App\Http\Controllers\Accounts\DataExportController;
use App\Models\LocaleSetting;
use App\Services\LanguageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('auth.login');
})->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Single Domain Mode + Locale URL prefix
|--------------------------------------------------------------------------
|
| When APP_SINGLE_DOMAIN is true, every portal below resolves to the same
| host. A bare "/" per portal would collide (last one registered wins), so
| each authenticated portal is prefixed by its own key instead:
| app.test/app, app.test/backoffice, app.test/account. Landing keeps the
| bare root and auth keeps its bare paths (app.test/login) either way,
| since neither defines a colliding "/" route across portals.
|
| Separately: example.com/ (primary language) | example.com/ar | example.com/ta
| — but only on landing and auth. app/account/backoffice never get a locale
| URL at all (no `/ar`, no `?lang=` — see SetLocale middleware): once
| signed in, the account's Preferred Language setting is authoritative
| everywhere on those three, so a URL-based override would just be a
| second, conflicting way to do the same thing for no benefit.
|
| Routes are registered fresh on every request, so this reads the current
| request's first URI segment and, if it names an active *non-primary*
| language, folds it into landing/auth's route prefix for this request
| only. The primary language keeps bare, unprefixed URLs. When both apply,
| the locale segment comes first: example.com/ar/login.
|
| Actually setting the app locale from this segment happens in SetLocale
| middleware (it runs after routing) — this block only decides which URI
| shape gets registered so route()/URL generation stays consistent with
| whatever the visitor is currently looking at.
|
| Wrapped in try/catch so a fresh install (migrations not yet run) doesn't
| crash route registration — it just falls back to unprefixed routes.
*/
$localeSegment = null;
try {
    if (LocaleSetting::isPathMode()) {
        $languages = app(LanguageService::class);
        $requested = request()->segment(1);

        if (is_string($requested) && $requested !== $languages->primaryCode() && in_array($requested, $languages->activeCodes(), true)) {
            $localeSegment = $requested;
        }
    }
} catch (Throwable $e) {
    // languages/locale_settings tables not migrated yet — no prefix routing available.
}

$localized = function ($registrar, ?string $segment = null) use ($localeSegment) {
    $segments = array_values(array_filter([$localeSegment, $segment], fn ($s) => $s !== null && $s !== ''));

    return $segments === [] ? $registrar : $registrar->prefix(implode('/', $segments));
};

// app/account/backoffice go through this instead — single-domain-mode's
// portal segment still applies, the locale segment never does.
$portalOnly = function ($registrar, ?string $segment = null) {
    return $segment === null ? $registrar : $registrar->prefix($segment);
};

$localized(
    Route::domain(config('multidomain.main_domain')),
)->group(function () {
    include __DIR__.'/landing.php';
});

$portalOnly(
    Route::domain(config('multidomain.sub_domains.app'))
        ->name('app.')
        ->middleware(['auth', 'phone.verified', 'email.grace', 'portal:user']),
    config('multidomain.single_domain') ? 'app' : null,
)->group(function () {
    include __DIR__.'/app.php';
});

$portalOnly(
    Route::domain(config('multidomain.sub_domains.backoffice'))
        ->name('backoffice.')
        ->middleware(['auth', 'phone.verified', 'email.grace', 'portal:staff']),
    config('multidomain.single_domain') ? 'backoffice' : null,
)->group(function () {
    include __DIR__.'/backoffice.php';
});

// Export download — token-authenticated, no session auth required
$portalOnly(
    Route::domain(config('multidomain.sub_domains.account'))
        ->name('account.'),
    config('multidomain.single_domain') ? 'account' : null,
)->group(function () {
    Route::get('export/{token}', DataExportController::class)->name('export.download');
});

$portalOnly(
    Route::domain(config('multidomain.sub_domains.account'))
        ->name('account.')
        ->middleware(['auth']),
    config('multidomain.single_domain') ? 'account' : null,
)->group(function () {
    include __DIR__.'/account.php';
});

$localized(
    Route::domain(config('multidomain.sub_domains.auth'))
        ->name('auth.'),
)->group(function () {
    include __DIR__.'/auth.php';
});
