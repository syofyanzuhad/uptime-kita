<?php

use App\Http\Controllers\Api\V1\PublicCheckController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Public limited instant uptime check endpoint (30 req/min per IP)
    Route::match(['get', 'post'], '/check', PublicCheckController::class)
        ->middleware('throttle:30,1')
        ->name('check');
});
