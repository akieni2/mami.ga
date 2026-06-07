<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\Ride;
use App\Support\GeoDistance;

class EstimatedArrivalService
{
    public function __construct(
        private readonly DistanceRefreshService $distanceRefreshService,
    ) {}

    /**
     * ETA pour un chauffeur vers la cible de suivi de la course.
     *
     * @return array{distance_km: float|null, eta_minutes: int|null}
     */
    public function forRide(Driver $driver, Ride $ride): array
    {
        $refreshed = $this->distanceRefreshService->refreshForRide($driver, $ride);

        return [
            'distance_km' => $refreshed['distance_km'],
            'eta_minutes' => $refreshed['eta_minutes'],
        ];
    }

    /**
     * ETA entre deux points GPS (préparation intégrations routage).
     *
     * @return array{distance_km: float, eta_minutes: int}
     */
    public function betweenPoints(
        float $fromLatitude,
        float $fromLongitude,
        float $toLatitude,
        float $toLongitude,
    ): array {
        $distanceKm = GeoDistance::kilometers(
            $fromLatitude,
            $fromLongitude,
            $toLatitude,
            $toLongitude,
        );

        return [
            'distance_km' => round($distanceKm, 3),
            'eta_minutes' => $this->distanceRefreshService->estimateEtaMinutes($distanceKm),
        ];
    }
}
