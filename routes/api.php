<?php

use App\Http\Controllers\Api\MeController;
use App\Support\Modules\Module;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API routes — /v1 on the api subdomain
|--------------------------------------------------------------------------
|
| Registered in bootstrap/app.php with the "api" middleware group, JSON
| responses and the "api" rate limiter. Every route below needs a Sanctum
| token; pick the ability it needs with `ability:read` / `ability:write`.
|
*/

Route::get('/', fn () => [
    'name' => config('app.name'),
    'version' => config('api.version'),
])->name('index');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', MeController::class)->middleware('ability:read')->name('me');

    // Routes contributed by enabled feature modules (app/Modules/*/routes/api.php).
    // A disabled module's routes are never registered, so they 404.
    Module::routes('api');
});
