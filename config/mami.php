<?php

return [
    'driver_search_radius_km' => (float) env('MAMI_DRIVER_SEARCH_RADIUS_KM', 10),
    'ride_base_price' => (float) env('MAMI_RIDE_BASE_PRICE', 500),
    'ride_price_per_km' => (float) env('MAMI_RIDE_PRICE_PER_KM', 250),

    'broadcast_prefix' => env('MAMI_BROADCAST_PREFIX', 'mami'),

    'driver_offline_threshold_seconds' => (int) env('MAMI_DRIVER_OFFLINE_THRESHOLD_SECONDS', 300),
    'eta_average_speed_kmh' => (float) env('MAMI_ETA_AVERAGE_SPEED_KMH', 25),
];
