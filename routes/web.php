<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/logout', function (Request $request) {
    Auth::logout();

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('auth.login');
})->middleware('auth')->name('logout');

Route::domain(config('justreadbible.main_domain'))
    ->group(function () {
        include __DIR__ . '/landing.php';
    });

Route::domain(config('justreadbible.sub_domains.app'))
    ->name('app.')
    ->middleware(['auth', 'phone.verified', 'email.grace', 'portal:user'])
    ->group(function () {
        include __DIR__ . '/app.php';
    });

Route::domain(config('justreadbible.sub_domains.backoffice'))
    ->name('backoffice.')
    ->middleware(['auth', 'phone.verified', 'email.grace', 'portal:staff'])
    ->group(function () {
        include __DIR__ . '/backoffice.php';
    });

// Export download — token-authenticated, no session auth required
Route::domain(config('justreadbible.sub_domains.account'))
    ->name('account.')
    ->group(function () {
        Route::get('export/{token}', \App\Http\Controllers\AccountExportController::class)->name('export.download');
    });

Route::domain(config('justreadbible.sub_domains.account'))
    ->name('account.')
    ->middleware(['auth'])
    ->group(function () {
        include __DIR__ . '/account.php';
    });

Route::domain(config('justreadbible.sub_domains.auth'))
    ->name('auth.')
    ->group(function () {
        include __DIR__ . '/auth.php';
    });
