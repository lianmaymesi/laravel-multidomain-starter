<?php

use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::livewire('login', 'pages::auth.login')->name('login');
    Route::livewire('register', 'pages::auth.register')->name('register');
    Route::livewire('forgot-password', 'pages::auth.forgot-password')->name('forgot-password');
    Route::livewire('reset-password/{token}',  'pages::auth.reset-password')->name('reset-password');
    Route::livewire('two-factor-challenge', 'pages::auth.two-factor-challenge')->name('two-factor-challenge');
});

Route::middleware(['auth', 'phone.verified'])->group(function () {
    Route::livewire('verify-email', 'pages::auth.verify-email')->name('verify-email');
    Route::livewire('two-factor-setup', 'pages::auth.two-factor-setup')->name('two-factor-setup');
});

Route::middleware('auth')->group(function () {
    Route::livewire('verify-phone', 'pages::auth.verify-phone')->name('verify-phone');
});
