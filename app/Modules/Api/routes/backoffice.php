<?php

use Illuminate\Support\Facades\Route;

// Loaded by Module::routes('backoffice') inside the backoffice route group.
Route::livewire('api', 'api::access')->name('api.index');
