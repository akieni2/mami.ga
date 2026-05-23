<?php

return [
    'default' => env('BROADCAST_CONNECTION', 'log'),

    'connections' => [
        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

        /*
         * Firebase / mobile clients can mirror these payloads via Cloud Functions.
         * Configure a custom driver when moving to production realtime delivery.
         */
        'firebase' => [
            'driver' => 'log',
        ],
    ],
];
