<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MAMI Taxi V2 — feature flags (docs/MAMI_TAXI_V2.md)
    |--------------------------------------------------------------------------
    */
    'taxi_v2_enabled' => (bool) env('MAMI_TAXI_V2', false),
    'dispatch_v2_enabled' => (bool) env('MAMI_DISPATCH_V2', false),

    'min_proposed_price' => (int) env('MAMI_MIN_PROPOSED_PRICE', 500),
    'max_proposed_price' => (int) env('MAMI_MAX_PROPOSED_PRICE', 500000),
    'pickup_label_min_length' => 3,
    'destination_label_min_length' => 3,

    'driver_search_radius_km' => (float) env('MAMI_DRIVER_SEARCH_RADIUS_KM', 10),
    'ride_base_price' => (float) env('MAMI_RIDE_BASE_PRICE', 500),
    'ride_price_per_km' => (float) env('MAMI_RIDE_PRICE_PER_KM', 250),

    'broadcast_prefix' => env('MAMI_BROADCAST_PREFIX', 'mami'),

    'driver_offline_threshold_seconds' => (int) env('MAMI_DRIVER_OFFLINE_THRESHOLD_SECONDS', 300),
    'eta_average_speed_kmh' => (float) env('MAMI_ETA_AVERAGE_SPEED_KMH', 25),

    /*
    |--------------------------------------------------------------------------
    | V2 dispatch (activé avec MAMI_DISPATCH_V2 — phases P3+)
    |--------------------------------------------------------------------------
    */
    'dispatch_radius_waves' => [
        ['min' => 0, 'max' => 1],
        ['min' => 1, 'max' => 3],
        ['min' => 3, 'max' => 5],
        ['min' => 5, 'max' => 10],
        ['min' => 10, 'max' => 20],
    ],
    'dispatch_wave_delay_seconds' => (int) env('MAMI_DISPATCH_WAVE_DELAY_SECONDS', 15),
    'offer_timeout_seconds' => (int) env('MAMI_OFFER_TIMEOUT_SECONDS', 30),
    'search_max_duration_hours' => (int) env('MAMI_SEARCH_MAX_DURATION_HOURS', 2),
    'scheduled_activation_lead_minutes' => (int) env('MAMI_SCHEDULED_ACTIVATION_LEAD_MINUTES', 10),
    'scheduled_deposit_percent' => (int) env('MAMI_SCHEDULED_DEPOSIT_PERCENT', 30),
    'scheduled_ride_lock_buffer_minutes' => (int) env('MAMI_SCHEDULED_LOCK_BUFFER_MINUTES', 30),
    'no_show_client_grace_minutes' => (int) env('MAMI_NO_SHOW_CLIENT_GRACE_MINUTES', 10),
    'no_show_driver_grace_minutes' => (int) env('MAMI_NO_SHOW_DRIVER_GRACE_MINUTES', 15),
];
