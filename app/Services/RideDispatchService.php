<?php

namespace App\Services;

use App\Enums\DriverStatus;
use App\Enums\RideEventType;
use App\Enums\RideStatus;
use App\Events\DriverArrived;
use App\Events\RideAccepted;
use App\Events\RideAssigned;
use App\Events\RideCompleted;
use App\Events\RideRequested;
use App\Events\RideStarted;
use App\Models\Driver;
use App\Models\Ride;
use App\Models\User;
use App\Support\GeoDistance;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RideDispatchService
{
    public function __construct(
        private readonly DriverLocationService $driverLocationService,
        private readonly RideEventRecorder $rideEventRecorder,
        private readonly DriverPresenceService $presenceService,
    ) {}

    public function requestRide(
        User $client,
        float $pickupLatitude,
        float $pickupLongitude,
        float $destinationLatitude,
        float $destinationLongitude,
    ): Ride {
        if ($client->isDriver()) {
            throw new RuntimeException('Drivers cannot request rides as clients.');
        }

        return DB::transaction(function () use (
            $client,
            $pickupLatitude,
            $pickupLongitude,
            $destinationLatitude,
            $destinationLongitude,
        ) {
            $driver = $this->findNearestAvailableDriver($pickupLatitude, $pickupLongitude);

            if ($driver === null) {
                throw new RuntimeException('No available drivers nearby.');
            }

            $ride = Ride::query()->create([
                'client_id' => $client->id,
                'driver_id' => $driver->id,
                'pickup_latitude' => $pickupLatitude,
                'pickup_longitude' => $pickupLongitude,
                'destination_latitude' => $destinationLatitude,
                'destination_longitude' => $destinationLongitude,
                'status' => RideStatus::Pending,
                'estimated_price' => $this->estimatePrice(
                    $pickupLatitude,
                    $pickupLongitude,
                    $destinationLatitude,
                    $destinationLongitude,
                ),
            ]);
            unset($driver->distance_km);

            $driver->update([
                'is_available' => false,
                'status' => DriverStatus::OnRide,
            ]);

            $ride = $ride->load(['client', 'driver.user', 'driver.vehicle']);

            $this->rideEventRecorder->record($ride, RideEventType::RideRequested, [
                'pickup_latitude' => $pickupLatitude,
                'pickup_longitude' => $pickupLongitude,
            ]);

            RideRequested::dispatch($ride);
            RideAssigned::dispatch($ride);

            return $ride;
        });
    }

    public function accept(Ride $ride, Driver $driver): Ride
    {
        $this->assertDriverOwnsRide($ride, $driver);

        if ($ride->status !== RideStatus::Pending) {
            throw new RuntimeException('Ride cannot be accepted in its current status.');
        }

        $ride->update(['status' => RideStatus::Accepted]);
        $ride = $ride->fresh(['client', 'driver.user', 'driver.vehicle']);

        $this->rideEventRecorder->record($ride, RideEventType::RideAccepted);
        RideAccepted::dispatch($ride);

        return $ride;
    }

    public function arrived(Ride $ride, Driver $driver): Ride
    {
        $this->assertDriverOwnsRide($ride, $driver);

        if ($ride->status !== RideStatus::Accepted) {
            throw new RuntimeException('Ride cannot be marked as arrived in its current status.');
        }

        $ride->update(['status' => RideStatus::Arrived]);
        $ride = $ride->fresh(['client', 'driver.user', 'driver.vehicle']);

        $this->rideEventRecorder->record($ride, RideEventType::DriverArrived);
        DriverArrived::dispatch($ride);

        return $ride;
    }

    public function start(Ride $ride, Driver $driver): Ride
    {
        $this->assertDriverOwnsRide($ride, $driver);

        if (! in_array($ride->status, [RideStatus::Accepted, RideStatus::Arrived], true)) {
            throw new RuntimeException('Ride cannot be started in its current status.');
        }

        $ride->update([
            'status' => RideStatus::Started,
            'started_at' => now(),
        ]);

        $ride = $ride->fresh(['client', 'driver.user', 'driver.vehicle']);

        $this->rideEventRecorder->record($ride, RideEventType::RideStarted);
        RideStarted::dispatch($ride);

        return $ride;
    }

    public function reject(Ride $ride, Driver $driver): Ride
    {
        $this->assertDriverOwnsRide($ride, $driver);

        if ($ride->status !== RideStatus::Pending) {
            throw new RuntimeException('Ride cannot be rejected in its current status.');
        }

        return DB::transaction(function () use ($ride, $driver) {
            $ride->update([
                'status' => RideStatus::Cancelled,
                'driver_id' => null,
            ]);

            $driver->update([
                'is_available' => true,
                'last_seen_at' => now(),
            ]);

            $this->presenceService->applyResolvedStatus($driver->fresh());

            $ride = $ride->fresh(['client']);

            $this->rideEventRecorder->record($ride, RideEventType::RideRejected, [
                'driver_id' => $driver->id,
            ]);

            return $ride;
        });
    }

    public function complete(Ride $ride, Driver $driver): Ride
    {
        $this->assertDriverOwnsRide($ride, $driver);

        if ($ride->status !== RideStatus::Started) {
            throw new RuntimeException('Ride cannot be completed in its current status.');
        }

        return DB::transaction(function () use ($ride, $driver) {
            $ride->update([
                'status' => RideStatus::Completed,
                'completed_at' => now(),
            ]);

            $driver->update([
                'is_available' => true,
                'last_seen_at' => now(),
            ]);

            $this->presenceService->applyResolvedStatus($driver->fresh());

            $ride = $ride->fresh(['client', 'driver.user', 'driver.vehicle']);

            $this->rideEventRecorder->record($ride, RideEventType::RideCompleted);
            RideCompleted::dispatch($ride);

            return $ride;
        });
    }

    private function findNearestAvailableDriver(float $latitude, float $longitude): ?Driver
    {
        $nearby = $this->driverLocationService->findNearby($latitude, $longitude);

        return $nearby->first();
    }

    private function estimatePrice(
        float $pickupLat,
        float $pickupLng,
        float $destLat,
        float $destLng,
    ): float {
        $distanceKm = GeoDistance::kilometers($pickupLat, $pickupLng, $destLat, $destLng);
        $base = (float) config('mami.ride_base_price');
        $perKm = (float) config('mami.ride_price_per_km');

        return round($base + ($distanceKm * $perKm), 2);
    }

    private function assertDriverOwnsRide(Ride $ride, Driver $driver): void
    {
        if ($ride->driver_id !== $driver->id) {
            throw new RuntimeException('This ride is not assigned to you.');
        }
    }
}
