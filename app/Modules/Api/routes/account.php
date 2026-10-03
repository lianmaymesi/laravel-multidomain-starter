<?php

use Illuminate\Support\Facades\Route;

// Loaded by Module::routes('account') inside the account portal group.
Route::livewire('api-tokens', 'api::tokens')->name('api-tokens');
