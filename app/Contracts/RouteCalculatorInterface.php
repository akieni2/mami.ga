<?php

namespace App\Contracts;

/**
 * Abstraction routage — implémentations futures : OSRM, GraphHopper, Valhalla.
 *
 * @phpstan-type RouteCoordinate array{latitude: float, longitude: float}
 * @phpstan-type RouteResult array{
 *     provider: string,
 *     distance_km: float,
 *     coordinates: list<RouteCoordinate>
 * }
 */
interface RouteCalculatorInterface
{
    /**
     * @return RouteResult
     */
    public function route(
        float $fromLatitude,
        float $fromLongitude,
        float $toLatitude,
        float $toLongitude,
    ): array;
}
