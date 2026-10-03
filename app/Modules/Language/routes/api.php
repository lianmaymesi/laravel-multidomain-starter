<?php

use App\Modules\Language\Http\Controllers\Api\LanguageController;
use Illuminate\Support\Facades\Route;

// Loaded by Module::routes('api') inside the authenticated /v1 API group
// (only while the API module is on). Languages are addressed by code.
Route::middleware('ability:languages:read')->group(function () {
    Route::get('languages', [LanguageController::class, 'index'])->name('languages.index');
    Route::get('languages/{language:code}', [LanguageController::class, 'show'])->name('languages.show');
});

Route::middleware('ability:languages:write')->group(function () {
    Route::post('languages', [LanguageController::class, 'store'])->name('languages.store');
    Route::patch('languages/{language:code}', [LanguageController::class, 'update'])->name('languages.update');
    Route::delete('languages/{language:code}', [LanguageController::class, 'destroy'])->name('languages.destroy');
});
