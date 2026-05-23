<?php

namespace App\Services;

use App\Enums\RideStatus;
use App\Models\Driver;
use App\Models\Ride;
use App\Support\GeoDistance;

class DistanceRefreshService
{
    /**
     * @return array{distance_km: float|null, eta_minutes: int|null, target_latitude: float, target_longitude: float}
     */
    public function refreshForRide(Driver $driver, Ride $ride): array
    {
        [$targetLat, $targetLng] = $this->trackingTarget($ride);

        if ($driver->latitude === null || $driver->longitude === null) {
            return [
                'distance_km' => null,
                'eta_minutes' => null,
                'target_latitude' => $targetLat,
                'target_longitude' => $targetLng,
            ];
        }

        $distanceKm = GeoDistance::kilometers(
            (float) $driver->latitude,
            (float) $driver->longitude,
            $targetLat,
            $targetLng,
        );

        return [
            'distance_km' => round($distanceKm, 3),
            'eta_minutes' => $this->estimateEtaMinutes($distanceKm),
            'target_latitude' => $targetLat,
            'target_longitude' => $targetLng,
        ];
    }

    public function estimateEtaMinutes(float $distanceKm): int
    {
        $speedKmh = max((float) config('mami.eta_average_speed_kmh', 25), 1);
        $hours = $distanceKm / $speedKmh;

        return max(1, (int) ceil($hours * 60));
    }

    /**
     * @return array{0: float, 1: float}
     */
    private function trackingTarget(Ride $ride): array
    {
        if (in_array($ride->status, [RideStatus::Started], true)) {
            return [(float) $ride->destination_latitude, (float) $ride->destination_longitude];
        }

        return [(float) $ride->pickup_latitude, (float) $ride->pickup_longitude];
    }
}
