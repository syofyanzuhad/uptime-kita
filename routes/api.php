<?php

use App\Http\Controllers\Api\V1\PublicCheckController;
use App\Http\Controllers\DomainCheckController;
use App\Http\Controllers\ManualMonitorCheckController;
use App\Http\Controllers\MonitorStatusStreamController;
use App\Http\Controllers\PublicServerStatsController;
use App\Http\Controllers\PublicToolsController;
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

// Tools API endpoints
Route::prefix('tools')->name('api.tools.')->middleware('throttle:30,1')->group(function () {
    Route::post('/domain-expiration', [PublicToolsController::class, 'apiCheckDomainExpiration'])->name('domain-expiration');
    Route::post('/ssl-check', [PublicToolsController::class, 'apiCheckSsl'])->name('ssl-check');
    Route::post('/dns-lookup', [PublicToolsController::class, 'apiLookupDns'])->name('dns-lookup');
    Route::post('/headers-check', [PublicToolsController::class, 'apiCheckHeaders'])->name('headers-check');
});

Route::get('/check-domain', DomainCheckController::class)
    ->middleware('throttle:20,1')
    ->name('api.check-domain');

// Public server stats API (for transparency badge)
Route::get('/server-stats', PublicServerStatsController::class)
    ->middleware('throttle:30,1')
    ->name('api.server-stats');

// SSE endpoint for real-time monitor status changes (public, no auth)
Route::get('/monitor-status-stream', MonitorStatusStreamController::class)
    ->middleware('throttle:10,1')
    ->name('api.monitor-status-stream');

// Manual uptime check — dispatches a single-monitor check job (public, throttled)
Route::post('/monitor/{domain}/check', ManualMonitorCheckController::class)
    ->where('domain', '[a-zA-Z0-9.-]+')
    ->middleware('throttle:3,1')
    ->name('api.monitor.check');

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Public limited instant uptime check endpoint (30 req/min per IP)
    Route::match(['get', 'post'], '/check', PublicCheckController::class)
        ->middleware('throttle:30,1')
        ->name('check');
});
