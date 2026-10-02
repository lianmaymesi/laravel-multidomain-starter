<?php

use App\Modules\Language\Http\Controllers\Api\LanguageController;
use Illuminate\Support\Facades\Route;

// Loaded by Module::routes('api') inside the authenticated /v1 API group.
// Languages are addressed by code: /v1/languages/en
Route::middleware('ability:read')->group(function () {
    Route::get('languages', [LanguageController::class, 'index'])->name('languages.index');
    Route::get('languages/{language:code}', [LanguageController::class, 'show'])->name('languages.show');
});

Route::middleware('ability:write')->group(function () {
    Route::post('languages', [LanguageController::class, 'store'])->name('languages.store');
    Route::patch('languages/{language:code}', [LanguageController::class, 'update'])->name('languages.update');
    Route::delete('languages/{language:code}', [LanguageController::class, 'destroy'])->name('languages.destroy');
});
