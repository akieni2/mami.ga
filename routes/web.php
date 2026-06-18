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

        Route::prefix('fiscal')->name('fiscal.')->group(function (): void {
            Route::get('/tax-types', [\App\Modules\Municipality\Http\Controllers\Admin\FiscalAdminController::class, 'taxTypes'])->name('tax-types');
            Route::post('/tax-types', [\App\Modules\Municipality\Http\Controllers\Admin\FiscalAdminController::class, 'storeTaxType'])->name('tax-types.store');
            Route::post('/tax-types/{taxType}/toggle', [\App\Modules\Municipality\Http\Controllers\Admin\FiscalAdminController::class, 'toggleTaxType'])->name('tax-types.toggle');
            Route::get('/rates', [\App\Modules\Municipality\Http\Controllers\Admin\FiscalAdminController::class, 'rates'])->name('rates');
            Route::post('/rates', [\App\Modules\Municipality\Http\Controllers\Admin\FiscalAdminController::class, 'storeRate'])->name('rates.store');
            Route::post('/rates/{rate}/deactivate', [\App\Modules\Municipality\Http\Controllers\Admin\FiscalAdminController::class, 'deactivateRate'])->name('rates.deactivate');
            Route::get('/targets', [\App\Modules\Municipality\Http\Controllers\Admin\FiscalAdminController::class, 'targets'])->name('targets');
            Route::post('/targets', [\App\Modules\Municipality\Http\Controllers\Admin\FiscalAdminController::class, 'storeTarget'])->name('targets.store');
            Route::get('/assignments', [\App\Modules\Municipality\Http\Controllers\Admin\FiscalAdminController::class, 'assignments'])->name('assignments');
            Route::post('/assignments', [\App\Modules\Municipality\Http\Controllers\Admin\FiscalAdminController::class, 'storeAssignment'])->name('assignments.store');
            Route::post('/assignments/{assignment}/toggle', [\App\Modules\Municipality\Http\Controllers\Admin\FiscalAdminController::class, 'toggleAssignment'])->name('assignments.toggle');
            Route::get('/obligations', [\App\Modules\Municipality\Http\Controllers\Admin\FiscalAdminController::class, 'obligations'])->name('obligations');
            Route::post('/obligations/generate', [\App\Modules\Municipality\Http\Controllers\Admin\FiscalAdminController::class, 'generateObligations'])->name('obligations.generate');
        });

        Route::get('/collection', [\App\Modules\Municipality\Http\Controllers\Admin\CashCollectionAdminController::class, 'dashboard'])->name('collection.dashboard');
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
