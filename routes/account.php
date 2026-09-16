<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::accounts.index')->name('index');
Route::livewire('/security', 'pages::accounts.security')->name('security');
Route::livewire('/export', 'pages::accounts.export')->name('export');
Route::livewire('/settings', 'pages::accounts.settings')->name('settings');

Route::middleware(['auth', 'phone.verified'])->group(function () {
    Route::livewire('two-factor-setup', 'pages::accounts.two-factor-setup')->name('two-factor-setup');
});
