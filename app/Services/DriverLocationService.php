<?php

namespace App\Services;

use App\Enums\RideEventType;
use App\Events\DriverLocationUpdated;
use App\Models\Driver;
use App\Models\DriverLocation;
use Illuminate\Support\Carbon;

class DriverLocationService
{
    public function __construct(
        private readonly DriverPresenceService $presenceService,
        private readonly DistanceRefreshService $distanceRefreshService,
        private readonly RideEventRecorder $rideEventRecorder,
    ) {}

    public function update(Driver $driver, float $latitude, float $longitude): Driver
    {
        $driver->update([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'last_seen_at' => Carbon::now(),
        ]);

        DriverLocation::query()->create([
            'driver_id' => $driver->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'recorded_at' => Carbon::now(),
        ]);

        $driver = $this->presenceService->applyResolvedStatus($driver->fresh());

        $activeRide = $driver->activeRide();
        $distanceKm = null;
        $etaMinutes = null;

        if ($activeRide !== null) {
            $metrics = $this->distanceRefreshService->refreshForRide($driver, $activeRide);

            $distanceKm = $metrics['distance_km'];
            $etaMinutes = $metrics['eta_minutes'];

            $this->rideEventRecorder->record(
                $activeRide,
                RideEventType::DriverLocationUpdated,
                [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'distance_km' => $distanceKm,
                    'eta_minutes' => $etaMinutes,
                ]
            );
        }

        DriverLocationUpdated::dispatch(
            $driver,
            $activeRide,
            $distanceKm,
            $etaMinutes
        );

        return $driver->load(['user', 'vehicle']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Driver>
     */
    public function findNearby(
        float $latitude,
        float $longitude,
        ?float $radiusKm = null
    ) {
        $radiusKm ??= (float) config('mami.driver_search_radius_km');

        return Driver::query()
            ->with(['user', 'vehicle'])
            ->where('is_available', true)
            ->where('status', \App\Enums\DriverStatus::Online->value)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(function (Driver $driver) use ($latitude, $longitude) {

                $distanceKm = \App\Support\GeoDistance::kilometers(
                    $latitude,
                    $longitude,
                    (float) $driver->latitude,
                    (float) $driver->longitude,
                );

                // Attribut calculé uniquement pour le tri/filtrage
                $driver->distance_km = $distanceKm;

                return $driver;
            })
            ->filter(fn (Driver $driver) => $driver->distance_km <= $radiusKm)
            ->sortBy('distance_km')
            ->values();
    }
}
\ No newline at end of file