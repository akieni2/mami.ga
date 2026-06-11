<?php

namespace App\Services;

use App\Support\GeoDistance;

/**
 * Estimation trajet (P1) — sans création de course ni dispatch.
 */
class RideEstimateService
{
    /**
     * @return array{
     *     distance_km: float,
     *     duration_minutes: int,
     *     suggested_price: float,
     *     estimated_price: float
     * }
     */
    public function estimate(
        float $pickupLatitude,
        float $pickupLongitude,
        float $destinationLatitude,
        float $destinationLongitude,
    ): array {
        $distanceKm = GeoDistance::kilometers(
            $pickupLatitude,
            $pickupLongitude,
            $destinationLatitude,
            $destinationLongitude,
        );

        $speedKmh = max(1.0, (float) config('mami.eta_average_speed_kmh', 25));
        $durationMinutes = max(1, (int) ceil(($distanceKm / $speedKmh) * 60));

        $base = (float) config('mami.ride_base_price');
        $perKm = (float) config('mami.ride_price_per_km');
        $suggestedPrice = round($base + ($distanceKm * $perKm), 2);

        return [
            'distance_km' => round($distanceKm, 3),
            'duration_minutes' => $durationMinutes,
            'suggested_price' => $suggestedPrice,
            'estimated_price' => $suggestedPrice,
        ];
    }
}
