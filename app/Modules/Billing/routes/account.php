<?php

use Illuminate\Support\Facades\Route;

// Loaded by Module::routes('account') inside the account route group.
Route::livewire('billing', 'billing::account')->name('billing');
