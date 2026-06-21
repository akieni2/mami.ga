<?php

use App\Modules\Municipality\Http\Controllers\CashSessionController;
use App\Modules\Municipality\Http\Controllers\EconomicOperatorController;
use App\Modules\Municipality\Http\Controllers\EconomicOperatorQrController;
use App\Modules\Municipality\Http\Controllers\FiscalAssignmentController;
use App\Modules\Municipality\Http\Controllers\FiscalCollectionController;
use App\Modules\Municipality\Http\Controllers\FiscalObligationController;
use App\Modules\Municipality\Http\Controllers\MunicipalReceiptController;
use App\Modules\Municipality\Http\Controllers\FiscalTargetController;
use App\Modules\Municipality\Http\Controllers\FiscalTaxRateController;
use App\Modules\Municipality\Http\Controllers\FiscalTaxTypeController;
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
    Route::get('/operators/{operator}/fiscal-summary', [FiscalCollectionController::class, 'operatorFiscalSummary']);
    Route::get('/operators/{operator}', [EconomicOperatorController::class, 'show']);
    Route::put('/operators/{operator}', [EconomicOperatorController::class, 'update']);
    Route::post('/operators/{operator}/inspect', [EconomicOperatorController::class, 'inspect']);

    Route::prefix('fiscal')->group(function (): void {
        Route::get('/taxes', [FiscalTaxTypeController::class, 'index']);
        Route::post('/taxes', [FiscalTaxTypeController::class, 'store']);
        Route::get('/taxes/{taxType}', [FiscalTaxTypeController::class, 'show']);
        Route::put('/taxes/{taxType}', [FiscalTaxTypeController::class, 'update']);
        Route::post('/taxes/{taxType}/activate', [FiscalTaxTypeController::class, 'activate']);
        Route::post('/taxes/{taxType}/deactivate', [FiscalTaxTypeController::class, 'deactivate']);

        Route::get('/rates', [FiscalTaxRateController::class, 'index']);
        Route::post('/rates', [FiscalTaxRateController::class, 'store']);
        Route::get('/rates/{rate}', [FiscalTaxRateController::class, 'show']);
        Route::post('/rates/{rate}/deactivate', [FiscalTaxRateController::class, 'deactivate']);

        Route::get('/targets', [FiscalTargetController::class, 'index']);
        Route::post('/targets', [FiscalTargetController::class, 'store']);
        Route::get('/targets/{target}', [FiscalTargetController::class, 'show']);

        Route::get('/assignments', [FiscalAssignmentController::class, 'index']);
        Route::post('/assignments', [FiscalAssignmentController::class, 'store']);
        Route::get('/assignments/{assignment}', [FiscalAssignmentController::class, 'show']);
        Route::post('/assignments/{assignment}/activate', [FiscalAssignmentController::class, 'activate']);
        Route::post('/assignments/{assignment}/deactivate', [FiscalAssignmentController::class, 'deactivate']);

        Route::get('/obligations', [FiscalObligationController::class, 'index']);
        Route::post('/obligations/generate', [FiscalObligationController::class, 'generate']);
        Route::get('/obligations/{obligation}', [FiscalObligationController::class, 'show']);
        Route::post('/obligations/{obligation}/cancel', [FiscalObligationController::class, 'cancel']);

        Route::get('/operator/{operator}/summary', [FiscalCollectionController::class, 'operatorSummary']);
        Route::post('/collections', [FiscalCollectionController::class, 'store']);
        Route::get('/collections', [FiscalCollectionController::class, 'index']);
        Route::get('/supervisor/dashboard', [FiscalCollectionController::class, 'supervisorDashboard']);

        Route::get('/cash-sessions/current', [CashSessionController::class, 'current']);
        Route::post('/cash-sessions/open', [CashSessionController::class, 'open']);
        Route::post('/cash-sessions/{cashSession}/close', [CashSessionController::class, 'close']);
        Route::get('/cash-sessions', [CashSessionController::class, 'index']);

        Route::get('/receipts', [MunicipalReceiptController::class, 'index']);
        Route::get('/receipts/{receipt}', [MunicipalReceiptController::class, 'show']);
        Route::get('/receipts/{receipt}/pdf/{format?}', [MunicipalReceiptController::class, 'downloadPdf']);
        Route::post('/receipts/{receipt}/reprint', [MunicipalReceiptController::class, 'reprint']);
        Route::post('/receipts/{receipt}/annul', [MunicipalReceiptController::class, 'annul']);
    });
});
