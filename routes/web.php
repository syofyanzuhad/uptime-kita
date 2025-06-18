<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UptimeMonitorController;

Route::get('/', function () {
    return Inertia::render('Dashboard');
})->name('home');

// Public route for public monitors
Route::get('/public-monitors', [UptimeMonitorController::class, 'public'])->name('monitor.public');

Route::middleware(['auth', 'verified'])->group(function () {
    // Resource route untuk CRUD monitor
    Route::resource('monitor', UptimeMonitorController::class);
});
Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
