<?php

use Illuminate\Support\Facades\Route;

// Loaded by Module::routes('backoffice') inside the backoffice route group.
Route::livewire('media', 'media::library')->name('media.index');
