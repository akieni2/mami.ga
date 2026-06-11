<?php

namespace App\Services;

use App\Models\Driver;
use App\Support\Dispatch\DispatchLogger;

class RideDispatchScoringService
{
    /**
     * @return array{score: float, distance_km: float}
     */
    public function score(Driver $driver, float $distanceKm, float $waveMaxKm): array
    {
        $weights = config('mami.dispatch_scoring_weights', [
            'distance' => 0.5,
            'availability' => 0.3,
            'rating' => 0.2,
        ]);

        $distanceScore = $waveMaxKm > 0
            ? max(0.0, 1.0 - ($distanceKm / $waveMaxKm))
            : 0.0;

        $availabilityScore = $driver->is_available ? 1.0 : 0.0;
        $ratingScore = min(1.0, max(0.0, ((float) $driver->rating) / 5.0));

        $score = ($weights['distance'] * $distanceScore)
            + ($weights['availability'] * $availabilityScore)
            + ($weights['rating'] * $ratingScore);

        DispatchLogger::scoring(sprintf(
            'Driver #%d score=%.2f distance=%.1fkm rating=%.1f',
            $driver->id,
            $score,
            $distanceKm,
            (float) $driver->rating,
        ));

        return [
            'score' => round($score, 4),
            'distance_km' => round($distanceKm, 3),
        ];
    }
}
