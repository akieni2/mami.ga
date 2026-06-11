<?php

namespace App\Support;

/**
 * Feature flags MAMI Taxi V2 — voir docs/MAMI_TAXI_V2.md
 */
class MamiFeatures
{
    public static function taxiV2Enabled(): bool
    {
        return (bool) config('mami.taxi_v2_enabled', false);
    }

    public static function dispatchV2Enabled(): bool
    {
        return (bool) config('mami.dispatch_v2_enabled', false);
    }

    /**
     * @return array<string, bool|int|float|array<string, mixed>>
     */
    public static function publicConfig(): array
    {
        return [
            'taxi_v2_enabled' => self::taxiV2Enabled(),
            'dispatch_v2_enabled' => self::dispatchV2Enabled(),
            'ride_base_price' => (float) config('mami.ride_base_price'),
            'ride_price_per_km' => (float) config('mami.ride_price_per_km'),
            'eta_average_speed_kmh' => (float) config('mami.eta_average_speed_kmh'),
        ];
    }
}
