<?php

use App\Modules\Municipality\Http\Controllers\MunicipalityModuleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'module:municipality'])->group(function (): void {
    Route::get('/status', [MunicipalityModuleController::class, 'status']);
});
