<?php

namespace App\Services;

use App\Contracts\RouteCalculatorInterface;
use App\Support\GeoDistance;

class RouteService implements RouteCalculatorInterface
{
    public function route(
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
            'provider' => 'straight_line',
            'distance_km' => round($distanceKm, 3),
            'coordinates' => $this->interpolate(
                $fromLatitude,
                $fromLongitude,
                $toLatitude,
                $toLongitude,
            ),
        ];
    }

    /**
     * Tracé simple (Sprint 03) — remplaçable par OSRM / GraphHopper / Valhalla.
     *
     * @return list<array{latitude: float, longitude: float}>
     */
    private function interpolate(
        float $fromLat,
        float $fromLng,
        float $toLat,
        float $toLng,
        int $segments = 24,
    ): array {
        $points = [];

        for ($i = 0; $i <= $segments; $i++) {
            $ratio = $segments === 0 ? 0 : $i / $segments;
            $points[] = [
                'latitude' => round($fromLat + ($toLat - $fromLat) * $ratio, 7),
                'longitude' => round($fromLng + ($toLng - $fromLng) * $ratio, 7),
            ];
        }

        return $points;
    }
}
