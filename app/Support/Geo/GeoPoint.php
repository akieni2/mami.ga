<?php

namespace App\Support\Geo;

use App\Models\Ride;

readonly class GeoPoint
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {}

    public static function fromRidePickup(Ride $ride): self
    {
        return new self(
            (float) $ride->pickup_latitude,
            (float) $ride->pickup_longitude,
        );
    }

    public static function librevilleCenter(): self
    {
        return new self(
            (float) config('mami.libreville_center_latitude', 0.4162),
            (float) config('mami.libreville_center_longitude', 9.4673),
        );
    }
}
