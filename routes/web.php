<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicMonitorController;
use App\Http\Controllers\UptimeMonitorController;
use App\Http\Controllers\PrivateMonitorController;
use App\Http\Controllers\StatisticMonitorController;
use App\Http\Controllers\SubscribeMonitorController;
use App\Http\Controllers\StatusPageController;
use App\Http\Controllers\PublicStatusPageController;

Route::get('/', function () {
    return Inertia::render('Dashboard');
})->name('home');

Route::get('/public-monitors', PublicMonitorController::class)->name('monitor.public');
Route::get('/statistic-monitor', StatisticMonitorController::class)->name('monitor.statistic');

// Public status page route
Route::get('/status/{path}', [PublicStatusPageController::class, 'show'])->name('status-page.public');

Route::middleware(['auth', 'verified'])->group(function () {
    // Route untuk private monitor
    Route::get('/private-monitors', PrivateMonitorController::class)->name('monitor.private');
    // Resource route untuk CRUD monitor
    Route::resource('monitor', UptimeMonitorController::class);
    // Route untuk subscribe monitor
    Route::post('/monitor/{monitorId}/subscribe', SubscribeMonitorController::class)->name('monitor.subscribe');

    // Get monitor history
    Route::get('/monitor/{monitor}/history', [UptimeMonitorController::class, 'getHistory'])->name('monitor.history');

    // Status page management routes
    Route::resource('status-pages', StatusPageController::class);
});
Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
