<?php

use App\Modules\Api\Http\Controllers\MeController;
use Illuminate\Support\Facades\Route;

// Loaded by Module::routes('api') inside the authenticated /v1 group.
Route::get('me', MeController::class)->middleware('ability:profile:read')->name('me');
