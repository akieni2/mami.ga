<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rides\RequestRideRequest;
use App\Http\Resources\RideResource;
use App\Models\Ride;
use App\Services\RideDispatchService;
use App\Services\RideTrackingService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class RideController extends Controller
{
    public function __construct(
        private readonly RideDispatchService $rideDispatchService,
        private readonly RideTrackingService $rideTrackingService,
    ) {}

    public function request(RequestRideRequest $request): JsonResponse
    {
        try {
            $ride = $this->rideDispatchService->requestRide(
                $request->user(),
                (float) $request->input('pickup_latitude'),
                (float) $request->input('pickup_longitude'),
                (float) $request->input('destination_latitude'),
                (float) $request->input('destination_longitude'),
            );
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success(
            (new RideResource($ride))->resolve(),
            'Ride created successfully',
            201,
        );
    }

    public function accept(Request $request, Ride $ride): JsonResponse
    {
        $driver = $request->user()->driver;

        if ($driver === null) {
            return ApiResponse::error('User is not a driver.', 403);
        }

        try {
            $ride = $this->rideDispatchService->accept($ride, $driver);
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success(
            (new RideResource($ride))->resolve(),
            'Ride accepted successfully',
        );
    }

    public function arrived(Request $request, Ride $ride): JsonResponse
    {
        $driver = $request->user()->driver;

        if ($driver === null) {
            return ApiResponse::error('User is not a driver.', 403);
        }

        try {
            $ride = $this->rideDispatchService->arrived($ride, $driver);
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success(
            (new RideResource($ride))->resolve(),
            'Driver arrived at pickup',
        );
    }

    public function start(Request $request, Ride $ride): JsonResponse
    {
        $driver = $request->user()->driver;

        if ($driver === null) {
            return ApiResponse::error('User is not a driver.', 403);
        }

        try {
            $ride = $this->rideDispatchService->start($ride, $driver);
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success(
            (new RideResource($ride))->resolve(),
            'Ride started successfully',
        );
    }

    public function complete(Request $request, Ride $ride): JsonResponse
    {
        $driver = $request->user()->driver;

        if ($driver === null) {
            return ApiResponse::error('User is not a driver.', 403);
        }

        try {
            $ride = $this->rideDispatchService->complete($ride, $driver);
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success(
            (new RideResource($ride))->resolve(),
            'Ride completed successfully',
        );
    }

    public function show(Request $request, Ride $ride): JsonResponse
    {
        if (! $this->canAccessRide($request, $ride)) {
            return ApiResponse::error('Unauthorized to view this ride.', 403);
        }

        $ride->load(['client', 'driver.user', 'driver.vehicle']);

        return ApiResponse::success(
            (new RideResource($ride))->resolve(),
            'Ride retrieved',
        );
    }

    public function tracking(Request $request, Ride $ride): JsonResponse
    {
        if (! $this->canAccessRide($request, $ride)) {
            return ApiResponse::error('Unauthorized to track this ride.', 403);
        }

        return ApiResponse::success(
            $this->rideTrackingService->snapshot($ride),
            'Ride tracking snapshot retrieved',
        );
    }

    public function history(Request $request): JsonResponse
    {
        $user = $request->user();
        $driver = $user->driver;

        $query = Ride::query()->with(['client', 'driver.user', 'driver.vehicle']);

        if ($driver !== null && $request->boolean('as_driver')) {
            $query->where('driver_id', $driver->id);
        } else {
            $query->where('client_id', $user->id);
        }

        $rides = $query->latest()->paginate(20);

        $rides->through(fn (Ride $ride) => (new RideResource($ride))->resolve());

        return ApiResponse::paginated($rides, 'Ride history retrieved');
    }

    private function canAccessRide(Request $request, Ride $ride): bool
    {
        $user = $request->user();
        $driver = $user->driver;

        return $ride->client_id === $user->id
            || ($driver !== null && $ride->driver_id === $driver->id);
    }
}
