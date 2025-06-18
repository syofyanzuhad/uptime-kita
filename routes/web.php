<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\UptimeMonitorController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    // Resource route untuk CRUD monitor
    Route::resource('monitor', UptimeMonitorController::class);
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->middleware(['auth', 'verified'])->name('dashboard');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
