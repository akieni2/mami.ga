<?php

use App\Modules\Carpool\Http\Controllers\CarpoolModuleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'module:carpool'])->group(function (): void {
    Route::get('/status', [CarpoolModuleController::class, 'status']);
});
