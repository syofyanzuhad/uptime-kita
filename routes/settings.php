<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\PasswordController;

Route::redirect('settings', '/settings/profile');

// Settings routes
Route::middleware('auth')
    ->prefix('settings')
    ->group(function () {
        Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        Route::get('password', [PasswordController::class, 'edit'])->name('password.edit');
        Route::put('password', [PasswordController::class, 'update'])->name('password.update');

        Route::get('appearance', function () {
            return Inertia::render('settings/Appearance');
        })->name('appearance');

        Route::resource('notifications', NotificationController::class);
        Route::patch('notifications/{notification}/toggle', [NotificationController::class, 'toggle'])->name('notifications.toggle');
    });
