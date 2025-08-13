<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TestFlashController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PrivateMonitorController;
use App\Http\Controllers\PublicMonitorController;
use App\Http\Controllers\PublicStatusPageController;
use App\Http\Controllers\StatisticMonitorController;
use App\Http\Controllers\StatusPageController;
use App\Http\Controllers\SubscribeMonitorController;
use App\Http\Controllers\UnsubscribeMonitorController;
use App\Http\Controllers\UptimeMonitorController;

Route::get('/', [DashboardController::class, 'index'])->name('home');

Route::get('/public-monitors', PublicMonitorController::class)->name('monitor.public');
Route::get('/statistic-monitor', StatisticMonitorController::class)->name('monitor.statistic');
Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Public status page route
Route::get('/status/{path}', [PublicStatusPageController::class, 'show'])->name('status-page.public');
Route::get('/status/{path}/monitors', [PublicStatusPageController::class, 'monitors'])->name('status-page.public.monitors');
Route::get('/monitor/{monitor}/latest-history', \App\Http\Controllers\LatestHistoryController::class)->name('monitor.latest-history');

Route::middleware(['auth', 'verified'])->group(function () {
    // Route untuk private monitor
    Route::get('/private-monitors', PrivateMonitorController::class)->name('monitor.private');
    // Resource route untuk CRUD monitor
    Route::resource('monitor', UptimeMonitorController::class);
    // Route untuk subscribe monitor
    Route::post('/monitor/{monitorId}/subscribe', SubscribeMonitorController::class)->name('monitor.subscribe');
    // Route untuk unsubscribe monitor
    Route::delete('/monitor/{monitorId}/unsubscribe', UnsubscribeMonitorController::class)->name('monitor.unsubscribe');

    // Route untuk toggle monitor active status
    Route::post('/monitor/{monitorId}/toggle-active', \App\Http\Controllers\ToggleMonitorActiveController::class)->name('monitor.toggle-active');

    // Get monitor history
    Route::get('/monitor/{monitor}/history', [UptimeMonitorController::class, 'getHistory'])->name('monitor.history');
    Route::get('/monitor/{monitor}/uptimes-daily', \App\Http\Controllers\UptimesDailyController::class)->name('monitor.uptimes-daily');

    // Monitor history API routes (using dynamic SQLite databases)
    Route::prefix('api/monitor/{monitorId}/history')->name('monitor.history.')->group(function () {
        Route::get('/', [\App\Http\Controllers\MonitorHistoryController::class, 'index'])->name('index');
        Route::get('/latest', [\App\Http\Controllers\MonitorHistoryController::class, 'latest'])->name('latest');
        Route::get('/statistics', [\App\Http\Controllers\MonitorHistoryController::class, 'statistics'])->name('statistics');
        Route::post('/cleanup', [\App\Http\Controllers\MonitorHistoryController::class, 'cleanup'])->name('cleanup');
    });

    // Status page management routes
    Route::resource('status-pages', StatusPageController::class);

    // Status page monitor association routes
    Route::post('/status-pages/{statusPage}/monitors', \App\Http\Controllers\StatusPageAssociateMonitorController::class)->name('status-pages.monitors.associate');
    Route::delete('/status-pages/{statusPage}/monitors/{monitor}', \App\Http\Controllers\StatusPageDisassociateMonitorController::class)->name('status-pages.monitors.disassociate');
    Route::get('/status-pages/{statusPage}/available-monitors', \App\Http\Controllers\StatusPageAvailableMonitorsController::class)->name('status-pages.monitors.available');
    Route::post('/status-page-monitor/reorder/{statusPage}', \App\Http\Controllers\StatusPageOrderController::class)->name('status-page-monitor.reorder');

    // User management routes
    Route::resource('users', \App\Http\Controllers\UserController::class);
});

// Test route for flash messages
Route::get('/test-flash', TestFlashController::class)->name('test.flash');
// route group for health check
Route::get('/health', \Spatie\Health\Http\Controllers\SimpleHealthCheckController::class)->name('health.index');
Route::middleware('auth')->prefix('health')->as('health.')->group(function () {
    Route::get('/json', \Spatie\Health\Http\Controllers\HealthCheckJsonResultsController::class)->name('json');
    Route::get('/results', \Spatie\Health\Http\Controllers\HealthCheckResultsController::class)->name('results');
});

Route::prefix('webhook')->as('webhook.')->group(function () {
    Route::post('/telegram', [\App\Http\Controllers\TelegramWebhookController::class, 'handle'])->name('telegram');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
