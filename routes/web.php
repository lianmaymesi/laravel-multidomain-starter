<?php

use App\Http\Controllers\Accounts\DataExportController;
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
| Single Domain Mode — URI prefixes instead of subdomains
|--------------------------------------------------------------------------
|
| When APP_SINGLE_DOMAIN is true, every portal below resolves to the same
| host. A bare "/" per portal would collide (last one registered wins), so
| each authenticated portal is prefixed by its own key instead:
| app.test/app, app.test/backoffice, app.test/account. Landing keeps the
| bare root and auth keeps its bare paths (app.test/login) either way,
| since neither defines a colliding "/" route across portals.
|
*/
$prefixed = function ($registrar, string $segment) {
    return config('multidomain.single_domain') ? $registrar->prefix($segment) : $registrar;
};

Route::domain(config('multidomain.main_domain'))
    ->group(function () {
        include __DIR__.'/landing.php';
    });

$prefixed(
    Route::domain(config('multidomain.sub_domains.app'))
        ->name('app.')
        ->middleware(['auth', 'phone.verified', 'email.grace', 'portal:user']),
    'app',
)->group(function () {
    include __DIR__.'/app.php';
});

$prefixed(
    Route::domain(config('multidomain.sub_domains.backoffice'))
        ->name('backoffice.')
        ->middleware(['auth', 'phone.verified', 'email.grace', 'portal:staff']),
    'backoffice',
)->group(function () {
    include __DIR__.'/backoffice.php';
});

// Export download — token-authenticated, no session auth required
$prefixed(
    Route::domain(config('multidomain.sub_domains.account'))
        ->name('account.'),
    'account',
)->group(function () {
    Route::get('export/{token}', DataExportController::class)->name('export.download');
});

$prefixed(
    Route::domain(config('multidomain.sub_domains.account'))
        ->name('account.')
        ->middleware(['auth']),
    'account',
)->group(function () {
    include __DIR__.'/account.php';
});

Route::domain(config('multidomain.sub_domains.auth'))
    ->name('auth.')
    ->group(function () {
        include __DIR__.'/auth.php';
    });
