<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::accounts.index')->name('index');

Route::middleware(['auth', 'phone.verified'])->group(function () {
    Route::livewire('two-factor-setup', 'pages::accounts.two-factor-setup')->name('two-factor-setup');
});
