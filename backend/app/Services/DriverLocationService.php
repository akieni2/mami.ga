<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\DriverLocation;
use Illuminate\Support\Carbon;

class DriverLocationService
{
    public function update(Driver $driver, float $latitude, float $longitude): Driver
    {
        $driver->update([
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);

        DriverLocation::query()->create([
            'driver_id' => $driver->id,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'recorded_at' => Carbon::now(),
        ]);

        return $driver->fresh(['user', 'vehicle']);
    }

    /**
     * @return \Illuminate\Support\Collection<int, Driver>
     */
    public function findNearby(float $latitude, float $longitude, ?float $radiusKm = null)
    {
        $radiusKm ??= (float) config('mami.driver_search_radius_km');

        return Driver::query()
            ->with(['user', 'vehicle'])
            ->where('is_available', true)
            ->where('status', \App\Enums\DriverStatus::Online)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get()
            ->map(function (Driver $driver) use ($latitude, $longitude) {
                $driver->distance_km = \App\Support\GeoDistance::kilometers(
                    $latitude,
                    $longitude,
                    (float) $driver->latitude,
                    (float) $driver->longitude,
                );

                return $driver;
            })
            ->filter(fn (Driver $driver) => $driver->distance_km <= $radiusKm)
            ->sortBy('distance_km')
            ->values();
    }
}
