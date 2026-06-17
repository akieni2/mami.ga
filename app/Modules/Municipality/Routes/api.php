<?php

use App\Modules\Municipality\Http\Controllers\EconomicOperatorController;
use App\Modules\Municipality\Http\Controllers\EconomicOperatorQrController;
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

    Route::get('/economic-categories', [EconomicOperatorController::class, 'categories']);
    Route::get('/operators/by-qr/{value}', [EconomicOperatorQrController::class, 'showByQr'])
        ->where('value', '.+');
    Route::get('/operators/map', [EconomicOperatorController::class, 'map']);
    Route::get('/operators/dashboard', [EconomicOperatorController::class, 'dashboard']);
    Route::get('/operators', [EconomicOperatorController::class, 'index']);
    Route::post('/operators', [EconomicOperatorController::class, 'store']);
    Route::get('/operators/{operator}/qrcode/png', [EconomicOperatorQrController::class, 'downloadPng']);
    Route::get('/operators/{operator}/qrcode/pdf', [EconomicOperatorQrController::class, 'downloadPdf']);
    Route::get('/operators/{operator}/business-card', [EconomicOperatorQrController::class, 'businessCard']);
    Route::post('/operators/{operator}/field-visits', [EconomicOperatorQrController::class, 'storeFieldVisit']);
    Route::get('/operators/{operator}', [EconomicOperatorController::class, 'show']);
    Route::put('/operators/{operator}', [EconomicOperatorController::class, 'update']);
    Route::post('/operators/{operator}/inspect', [EconomicOperatorController::class, 'inspect']);
});
