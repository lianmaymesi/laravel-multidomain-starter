<?php

use Illuminate\Support\Facades\Route;

Route::domain(config('justreadbible.main_domain'))->group(function () {
    include __DIR__.'/landing.php';
});

Route::domain(config('justreadbible.sub_domains.app'))->name('app.')->group(function () {
    include __DIR__.'/app.php';
});

Route::domain(config('justreadbible.sub_domains.backoffice'))->name('backoffice.')->group(function () {
    include __DIR__.'/backoffice.php';
});

Route::domain(config('justreadbible.sub_domains.account'))->name('account.')->group(function () {
    include __DIR__.'/account.php';
});

Route::domain(config('justreadbible.sub_domains.auth'))->name('auth.')->group(function () {
    include __DIR__.'/auth.php';
});
