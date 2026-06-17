<?php

use App\Modules\Commerce\Http\Controllers\CommerceModuleController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'module:commerce'])->group(function (): void {
    Route::get('/status', [CommerceModuleController::class, 'status']);
});
