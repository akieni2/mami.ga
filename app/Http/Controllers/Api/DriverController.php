<?php

namespace App\Http\Controllers\Api;

use App\Enums\DriverStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Drivers\UpdateDriverLocationRequest;
use App\Http\Resources\DriverResource;
use App\Services\DriverLocationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function __construct(
        private readonly DriverLocationService $driverLocationService,
    ) {}

    public function nearby(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_km' => ['nullable', 'numeric', 'min:0.1', 'max:50'],
        ]);

        $drivers = $this->driverLocationService->findNearby(
            (float) $validated['latitude'],
            (float) $validated['longitude'],
            isset($validated['radius_km']) ? (float) $validated['radius_km'] : null,
        );

        return ApiResponse::success(
            DriverResource::collection($drivers)->resolve(),
            'Nearby drivers retrieved',
        );
    }

    public function updateLocation(UpdateDriverLocationRequest $request): JsonResponse
    {
        $driver = $request->user()->driver;

        if ($driver === null) {
            return ApiResponse::error('User is not a driver.', 403);
        }

        $driver = $this->driverLocationService->update(
            $driver,
            (float) $request->input('latitude'),
            (float) $request->input('longitude'),
        );

        return ApiResponse::success(
            (new DriverResource($driver))->resolve(),
            'Driver location updated',
        );
    }

    public function updateAvailability(Request $request): JsonResponse
    {
        $driver = $request->user()->driver;

        if ($driver === null) {
            return ApiResponse::error('User is not a driver.', 403);
        }

        $validated = $request->validate([
            'is_available' => ['required', 'boolean'],
        ]);

        $isAvailable = (bool) $validated['is_available'];

        $driver->update([
            'is_available' => $isAvailable,
            'status' => $isAvailable ? DriverStatus::Online : DriverStatus::Offline,
        ]);

        return ApiResponse::success(
            (new DriverResource($driver->fresh(['user', 'vehicle'])))->resolve(),
            'Driver availability updated',
        );
    }
}
