<?php

use Illuminate\Support\Facades\Route;

// Loaded by Module::routes('backoffice') inside the backoffice route group.
Route::livewire('activity', 'activity::index')->name('activity.index');
