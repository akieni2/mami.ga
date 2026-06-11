<?php

namespace App\Support;

use App\Enums\LocationSource;

/**
 * Détermine comment un point départ/destination a été saisi (P2B analytics).
 */
class LocationSourceResolver
{
    public static function resolve(string $label, ?float $latitude, ?float $longitude): LocationSource
    {
        if ($latitude === null || $longitude === null) {
            return LocationSource::Text;
        }

        $trimmed = trim($label);

        if ($trimmed === '' || self::isCoordinateLabel($trimmed)) {
            return LocationSource::Map;
        }

        return LocationSource::Hybrid;
    }

    private static function isCoordinateLabel(string $label): bool
    {
        return (bool) preg_match(
            '/^-?\d{1,2}\.\d+,\s*-?\d{1,3}\.\d+$/',
            $label,
        );
    }
}
