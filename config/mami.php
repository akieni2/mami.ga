<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MAMI Super App — feature flags globaux
    |--------------------------------------------------------------------------
    */
    'super_app_enabled' => (bool) env('MAMI_SUPER_APP', true),

    'modules' => [
        'taxi' => true,
        'carpool' => (bool) env('MAMI_MODULE_CARPOOL', false),
        'transport' => (bool) env('MAMI_MODULE_TRANSPORT', false),
        'commerce' => (bool) env('MAMI_MODULE_COMMERCE', false),
        'municipality' => (bool) env('MAMI_MODULE_MUNICIPALITY', false),
    ],

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
    'driver_gps_freshness_seconds' => (int) env('MAMI_DRIVER_GPS_FRESHNESS_SECONDS', 120),
    'driver_gps_max_accuracy_meters' => (float) env('MAMI_DRIVER_GPS_MAX_ACCURACY_METERS', 100),
    'driver_location_history_minutes' => (int) env('MAMI_DRIVER_LOCATION_HISTORY_MINUTES', 10),
    'eta_average_speed_kmh' => (float) env('MAMI_ETA_AVERAGE_SPEED_KMH', 25),

    /*
    |--------------------------------------------------------------------------
    | V2 dispatch (activé avec MAMI_DISPATCH_V2 — phases P3+)
    |--------------------------------------------------------------------------
    */
    'dispatch_radius_waves' => [
        ['max' => 1],
        ['max' => 3],
        ['max' => 5],
        ['max' => 10],
        ['max' => 20],
    ],
    'dispatch_wave_delay_seconds' => (int) env('MAMI_DISPATCH_WAVE_DELAY_SECONDS', 15),
    'dispatch_wave_max_drivers' => (int) env('MAMI_DISPATCH_WAVE_MAX_DRIVERS', 5),
    'offer_timeout_seconds' => (int) env('MAMI_OFFER_TIMEOUT_SECONDS', 120),
    'search_max_duration_hours' => (int) env('MAMI_SEARCH_MAX_DURATION_HOURS', 2),
    'dispatch_scoring_weights' => [
        'distance' => (float) env('MAMI_DISPATCH_SCORE_DISTANCE', 0.5),
        'availability' => (float) env('MAMI_DISPATCH_SCORE_AVAILABILITY', 0.3),
        'rating' => (float) env('MAMI_DISPATCH_SCORE_RATING', 0.2),
    ],
    'libreville_center_latitude' => (float) env('MAMI_LIBREVILLE_LAT', 0.4162),
    'libreville_center_longitude' => (float) env('MAMI_LIBREVILLE_LNG', 9.4673),
    'scheduled_activation_lead_minutes' => (int) env('MAMI_SCHEDULED_ACTIVATION_LEAD_MINUTES', 10),
    'scheduled_deposit_percent' => (int) env('MAMI_SCHEDULED_DEPOSIT_PERCENT', 30),
    'scheduled_ride_lock_buffer_minutes' => (int) env('MAMI_SCHEDULED_LOCK_BUFFER_MINUTES', 30),
    'no_show_client_grace_minutes' => (int) env('MAMI_NO_SHOW_CLIENT_GRACE_MINUTES', 10),
    'no_show_driver_grace_minutes' => (int) env('MAMI_NO_SHOW_DRIVER_GRACE_MINUTES', 15),

    /*
    |--------------------------------------------------------------------------
    | Municipality V3 — recouvrement terrain
    |--------------------------------------------------------------------------
    */
    'municipality_collection_max_gps_accuracy_m' => (float) env('MAMI_MUNICIPALITY_COLLECTION_MAX_GPS_ACCURACY_M', 50),

    'municipality_finance' => [
        'require_mission_for_cash_session' => (bool) env('MAMI_MUNICIPALITY_REQUIRE_MISSION', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | URLs publiques (domaines MAMI.GA)
    |--------------------------------------------------------------------------
    */
    'urls' => [
        'api' => rtrim((string) env('MAMI_API_URL', 'https://api.mami.ga'), '/'),
        'portal' => rtrim((string) env('MAMI_PORTAL_URL', 'https://mami.ga'), '/'),
        'admin' => rtrim((string) env('MAMI_ADMIN_URL', 'https://admin.mami.ga'), '/'),
        'websocket' => (string) env('MAMI_WEBSOCKET_URL', 'wss://ws.mami.ga'),
    ],
];
