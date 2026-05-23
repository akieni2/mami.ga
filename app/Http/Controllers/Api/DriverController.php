<?php

namespace App\Http\Controllers\Api;

use App\Enums\DriverStatus;
use App\Enums\RideStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Drivers\UpdateDriverLocationRequest;
use App\Http\Resources\DriverResource;
use App\Models\Driver;
use App\Models\Ride;
use App\Services\DriverLocationService;
use App\Services\DriverPresenceService;
use App\Services\RideTrackingService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function __construct(
        private readonly DriverLocationService $driverLocationService,
        private readonly DriverPresenceService $presenceService,
        private readonly RideTrackingService $rideTrackingService,
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
            'last_seen_at' => now(),
            'status' => $isAvailable ? DriverStatus::Online : DriverStatus::Offline,
        ]);

        $driver = $this->presenceService->applyResolvedStatus($driver);

        return ApiResponse::success(
            (new DriverResource($driver->fresh(['user', 'vehicle'])))->resolve(),
            'Driver availability updated',
        );
    }

    public function liveLocation(Request $request, Driver $driver): JsonResponse
    {
        $user = $request->user();

        if ($user->driver?->id === $driver->id) {
            $activeRide = $driver->activeRide();

            if ($activeRide === null) {
                return ApiResponse::success([
                    'driver_id' => $driver->id,
                    'latitude' => $driver->latitude !== null ? (float) $driver->latitude : null,
                    'longitude' => $driver->longitude !== null ? (float) $driver->longitude : null,
                    'presence' => $driver->presenceStatus(),
                    'last_seen_at' => $driver->last_seen_at?->toIso8601String(),
                    'ride_id' => null,
                ], 'Driver live location retrieved');
            }

            return ApiResponse::success(
                $this->rideTrackingService->liveLocationForDriver($activeRide),
                'Driver live location retrieved',
            );
        }

        $ride = Ride::query()
            ->where('driver_id', $driver->id)
            ->where('client_id', $user->id)
            ->whereNotIn('status', [RideStatus::Completed, RideStatus::Cancelled])
            ->latest('id')
            ->first();

        if ($ride === null) {
            return ApiResponse::error('Unauthorized to view this driver location.', 403);
        }

        return ApiResponse::success(
            $this->rideTrackingService->liveLocationForDriver($ride),
            'Driver live location retrieved',
        );
    }
}
