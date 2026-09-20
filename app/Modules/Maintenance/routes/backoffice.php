<?php

use Illuminate\Support\Facades\Route;

// Loaded by Module::routes('backoffice') inside the backoffice route group —
// inherits its domain, prefix, "backoffice." name prefix and middleware.
Route::livewire('maintenance', 'maintenance::index')->name('maintenance.index');
