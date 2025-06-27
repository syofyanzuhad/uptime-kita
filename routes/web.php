<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicMonitorController;
use App\Http\Controllers\UptimeMonitorController;
use App\Http\Controllers\PrivateMonitorController;
use App\Http\Controllers\StatisticMonitorController;
use App\Http\Controllers\SubscribeMonitorController;

Route::get('/', function () {
    return Inertia::render('Dashboard');
})->name('home');

// Public route for public monitors
Route::get('/public-monitors', [UptimeMonitorController::class, 'public'])->name('monitor.public');

Route::middleware(['auth', 'verified'])->group(function () {
    // Route untuk public monitor
    Route::get('/public-monitors', PublicMonitorController::class)->name('monitor.public');
    // Route untuk private monitor
    Route::get('/private-monitors', PrivateMonitorController::class)->name('monitor.private');
    // Route untuk subscribe monitor
    Route::post('/monitor/{monitor}/subscribe', SubscribeMonitorController::class)->name('monitor.subscribe');
    // Route untuk statistik monitor
    Route::get('/statistic-monitor', StatisticMonitorController::class)->name('monitor.statistic');
    // Resource route untuk CRUD monitor
    Route::resource('monitor', UptimeMonitorController::class);

    // Subscribe to public monitor
    Route::post('/monitor/{monitor}/subscribe', [UptimeMonitorController::class, 'subscribe'])->name('monitor.subscribe');

    // Get monitor history
    Route::get('/monitor/{monitor}/history', [UptimeMonitorController::class, 'getHistory'])->name('monitor.history');
});
Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
