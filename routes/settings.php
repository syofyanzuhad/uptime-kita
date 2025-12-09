<?php

use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ServerResourceController;
use App\Http\Controllers\Settings\AppearanceController;
use App\Http\Controllers\Settings\DatabaseBackupController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;

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

        Route::get('appearance', AppearanceController::class)->name('appearance');

        Route::get('database', [DatabaseBackupController::class, 'index'])->name('database.index');
        Route::get('database/download', [DatabaseBackupController::class, 'download'])->name('database.download');
        Route::post('database/restore', [DatabaseBackupController::class, 'restore'])->name('database.restore');

        Route::get('server-resources', [ServerResourceController::class, 'index'])->name('server-resources.index');

        Route::resource('notifications', NotificationController::class);
        Route::patch('notifications/{notification}/toggle', [NotificationController::class, 'toggle'])->name('notifications.toggle');
    });

// API route for server resources polling (authenticated)
Route::middleware('auth')
    ->prefix('api')
    ->group(function () {
        Route::get('server-resources', [ServerResourceController::class, 'metrics'])->name('api.server-resources');
    });
