<?php

use App\Modules\Municipality\Http\Controllers\MunicipalityModuleController;
use App\Modules\Municipality\Http\Controllers\MunicipalityReportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'module:municipality'])->group(function (): void {
    Route::get('/status', [MunicipalityModuleController::class, 'status']);

    Route::get('/reports/map', [MunicipalityReportController::class, 'map']);
    Route::get('/reports', [MunicipalityReportController::class, 'index']);
    Route::post('/reports', [MunicipalityReportController::class, 'store']);
    Route::get('/reports/{report}', [MunicipalityReportController::class, 'show']);
    Route::post('/reports/{report}/assign', [MunicipalityReportController::class, 'assign']);
    Route::post('/reports/{report}/status', [MunicipalityReportController::class, 'updateStatus']);
});
