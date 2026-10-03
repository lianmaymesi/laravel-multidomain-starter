<?php

use Illuminate\Support\Facades\Route;

// Loaded by Module::routes('backoffice') inside the backoffice route group.
Route::livewire('languages', 'language::index')->name('languages.index');
Route::livewire('translations/{scope?}', 'language::translations')->name('translations.index');
