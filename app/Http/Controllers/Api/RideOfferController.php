<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\RideOfferResource;
use App\Http\Resources\RideResource;
use App\Models\Ride;
use App\Models\RideOffer;
use App\Services\RideOfferService;
use App\Support\ApiResponse;
use App\Support\Dispatch\DispatchLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class RideOfferController extends Controller
{
    public function __construct(
        private readonly RideOfferService $rideOfferService,
    ) {}

    public function current(Request $request): JsonResponse
    {
        $driver = $request->user()->driver;

        if ($driver === null) {
            DispatchLogger::offersApi('driver profile missing user='.$request->user()->id);

            return ApiResponse::error('User is not a driver.', 403);
        }

        $offers = $this->rideOfferService->pendingOffersForDriver($driver);

        DispatchLogger::offersApi(
            'driver #'.$driver->id.' pending_count='.$offers->count(),
        );

        return ApiResponse::success(
            RideOfferResource::collection($offers)->resolve(),
            'Pending ride offers retrieved',
        );
    }

    public function accept(Request $request, Ride $ride, RideOffer $offer): JsonResponse
    {
        $driver = $request->user()->driver;

        if ($driver === null) {
            return ApiResponse::error('User is not a driver.', 403);
        }

        if ($offer->ride_id !== $ride->id) {
            return ApiResponse::error('Offer does not belong to this ride.', 422);
        }

        try {
            $ride = $this->rideOfferService->accept($offer, $driver);
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success(
            (new RideResource($ride))->resolve(),
            'Ride offer accepted',
        );
    }

    public function reject(Request $request, Ride $ride, RideOffer $offer): JsonResponse
    {
        $driver = $request->user()->driver;

        if ($driver === null) {
            return ApiResponse::error('User is not a driver.', 403);
        }

        if ($offer->ride_id !== $ride->id) {
            return ApiResponse::error('Offer does not belong to this ride.', 422);
        }

        try {
            $offer = $this->rideOfferService->reject($offer, $driver);
        } catch (RuntimeException $exception) {
            return ApiResponse::error($exception->getMessage(), 422);
        }

        return ApiResponse::success(
            (new RideOfferResource($offer))->resolve(),
            'Ride offer rejected',
        );
    }
}
