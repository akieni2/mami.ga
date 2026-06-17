<?php

use App\Modules\Transport\Http\Controllers\TransportModuleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'module:transport'])->group(function (): void {
    Route::get('/status', [TransportModuleController::class, 'status']);
});
