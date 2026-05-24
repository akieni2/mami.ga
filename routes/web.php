<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\LiveDataController;
use App\Http\Controllers\Admin\LiveMapController;
use App\Http\Controllers\Admin\RideController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::get('/', function () {
    return auth()->check() && auth()->user()->isAdmin()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', 'admin'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::get('/drivers', [DriverController::class, 'index'])->name('admin.drivers.index');
    Route::get('/rides', [RideController::class, 'index'])->name('admin.rides.index');
    Route::get('/map', [LiveMapController::class, 'index'])->name('admin.map.index');

    Route::prefix('admin/live')->name('admin.live.')->group(function (): void {
        Route::get('/dashboard', [LiveDataController::class, 'dashboard'])->name('dashboard');
        Route::get('/drivers', [LiveDataController::class, 'drivers'])->name('drivers');
        Route::get('/map', [LiveDataController::class, 'map'])->name('map');
    });
});
