<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

Route::domain(config('justreadbible.domains.accounts'))->group(function () {
    require __DIR__ . '/accounts.php';
});

Route::domain(config('justreadbible.domains.backoffice'))->group(function () {
    require __DIR__ . '/backoffice.php';
});

Route::domain(config('justreadbible.domains.user'))->group(function () {
    require __DIR__ . '/app.php';
});
