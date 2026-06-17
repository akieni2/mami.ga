<?php

use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DriverApplicationController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\LiveDataController;
use App\Http\Controllers\Admin\LiveMapController;
use App\Http\Controllers\Admin\ReportsController;
use App\Http\Controllers\Admin\RideController;
use Illuminate\Support\Facades\Route;

require __DIR__.'/auth.php';

Route::get('/', function () {
    return auth()->check() && auth()->user()->isAdmin()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/rides', [RideController::class, 'index'])->name('rides.index');
    Route::get('/rides/{ride}', [RideController::class, 'show'])->name('rides.show');
    Route::get('/drivers', [DriverController::class, 'index'])->name('drivers.index');
    Route::get('/drivers/{driver}/live', [DriverController::class, 'live'])->name('drivers.live');
    Route::get('/drivers/{driver}', [DriverController::class, 'show'])->name('drivers.show');
    Route::get('/driver-applications', [DriverApplicationController::class, 'index'])->name('driver-applications.index');
    Route::get('/driver-applications/{driverApplication}', [DriverApplicationController::class, 'show'])->name('driver-applications.show');
    Route::post('/driver-applications/{driverApplication}/approve', [DriverApplicationController::class, 'approve'])->name('driver-applications.approve');
    Route::post('/driver-applications/{driverApplication}/reject', [DriverApplicationController::class, 'reject'])->name('driver-applications.reject');
    Route::get('/clients', [ClientController::class, 'index'])->name('clients.index');
    Route::get('/clients/{user}', [ClientController::class, 'show'])->name('clients.show');
    Route::get('/map', [LiveMapController::class, 'index'])->name('map.index');
    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');

    Route::prefix('municipality')->name('municipality.')->middleware('module:municipality')->group(function (): void {
        Route::get('/reports', [\App\Modules\Municipality\Http\Controllers\Admin\MunicipalityReportAdminController::class, 'index'])->name('reports.index');
        Route::get('/reports/{report}', [\App\Modules\Municipality\Http\Controllers\Admin\MunicipalityReportAdminController::class, 'show'])->name('reports.show');
        Route::post('/reports/{report}/assign', [\App\Modules\Municipality\Http\Controllers\Admin\MunicipalityReportAdminController::class, 'assign'])->name('reports.assign');
        Route::post('/reports/{report}/status', [\App\Modules\Municipality\Http\Controllers\Admin\MunicipalityReportAdminController::class, 'updateStatus'])->name('reports.status');
        Route::get('/map', [\App\Modules\Municipality\Http\Controllers\Admin\MunicipalityMapAdminController::class, 'index'])->name('map.index');
        Route::get('/map/geojson', [\App\Modules\Municipality\Http\Controllers\Admin\MunicipalityMapAdminController::class, 'geojson'])->name('map.geojson');
    });

    Route::prefix('live')->name('live.')->group(function (): void {
        Route::get('/dashboard', [LiveDataController::class, 'dashboard'])->name('dashboard');
        Route::get('/drivers', [LiveDataController::class, 'drivers'])->name('drivers');
        Route::get('/drivers/{driver}', [LiveDataController::class, 'driver'])->name('driver');
        Route::get('/map', [LiveDataController::class, 'map'])->name('map');
        Route::get('/stats', [LiveDataController::class, 'stats'])->name('stats');
    });
});

// Rétrocompatibilité URLs Phase 2
Route::middleware(['auth', 'admin'])->group(function (): void {
    Route::redirect('/dashboard', '/admin/dashboard');
    Route::redirect('/drivers', '/admin/drivers');
    Route::redirect('/rides', '/admin/rides');
    Route::redirect('/map', '/admin/map');
});
