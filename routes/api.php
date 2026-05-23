<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DriverController;
use App\Http\Controllers\Api\RideController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/drivers/nearby', [DriverController::class, 'nearby']);
    Route::post('/drivers/location/update', [DriverController::class, 'updateLocation']);
    Route::post('/drivers/availability', [DriverController::class, 'updateAvailability']);

    Route::post('/rides/request', [RideController::class, 'request']);
    Route::get('/rides/history', [RideController::class, 'history']);
    Route::post('/rides/{ride}/accept', [RideController::class, 'accept']);
    Route::post('/rides/{ride}/start', [RideController::class, 'start']);
    Route::post('/rides/{ride}/complete', [RideController::class, 'complete']);
    Route::get('/rides/{ride}', [RideController::class, 'show']);
});
