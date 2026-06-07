<?php

namespace App\Services;

use App\Models\Ride;

class RideTrackingService
{
    public function __construct(
        private readonly DistanceRefreshService $distanceRefreshService,
        private readonly EstimatedArrivalService $estimatedArrivalService,
        private readonly RouteService $routeService,
    ) {}

    public function snapshot(Ride $ride): array
    {
        $ride->load(['client', 'driver.user', 'driver.vehicle', 'events' => fn ($q) => $q->latest()]);

        $driver = $ride->driver;
        $distance = $driver
            ? $this->distanceRefreshService->refreshForRide($driver, $ride)
            : [
                'distance_km' => null,
                'eta_minutes' => null,
                'target_latitude' => (float) $ride->pickup_latitude,
                'target_longitude' => (float) $ride->pickup_longitude,
            ];

        $eta = $driver
            ? $this->estimatedArrivalService->forRide($driver, $ride)
            : ['distance_km' => null, 'eta_minutes' => null];

        $routeFrom = $driver && $driver->latitude !== null && $driver->longitude !== null
            ? [(float) $driver->latitude, (float) $driver->longitude]
            : [(float) $ride->pickup_latitude, (float) $ride->pickup_longitude];

        $route = $this->routeService->route(
            $routeFrom[0],
            $routeFrom[1],
            $distance['target_latitude'],
            $distance['target_longitude'],
        );

        return [
            'ride' => [
                'id' => $ride->id,
                'status' => $ride->status->value,
                'pickup_latitude' => (float) $ride->pickup_latitude,
                'pickup_longitude' => (float) $ride->pickup_longitude,
                'destination_latitude' => (float) $ride->destination_latitude,
                'destination_longitude' => (float) $ride->destination_longitude,
                'started_at' => $ride->started_at?->toIso8601String(),
                'completed_at' => $ride->completed_at?->toIso8601String(),
            ],
            'driver' => $driver ? [
                'id' => $driver->id,
                'latitude' => $driver->latitude !== null ? (float) $driver->latitude : null,
                'longitude' => $driver->longitude !== null ? (float) $driver->longitude : null,
                'presence' => $driver->presenceStatus(),
                'last_seen_at' => $driver->last_seen_at?->toIso8601String(),
            ] : null,
            'tracking' => [
                'distance_km' => $distance['distance_km'],
                'eta_minutes' => $distance['eta_minutes'],
                'target_latitude' => $distance['target_latitude'],
                'target_longitude' => $distance['target_longitude'],
                'estimated_arrival' => $eta,
            ],
            'route' => $route,
            'events' => $ride->events->map(fn ($event) => [
                'id' => $event->id,
                'event_type' => $event->event_type->value,
                'payload' => $event->payload,
                'created_at' => $event->created_at?->toIso8601String(),
            ])->values()->all(),
        ];
    }

    public function liveLocationForDriver(Ride $ride): array
    {
        $driver = $ride->driver;
        $distance = $this->distanceRefreshService->refreshForRide($driver, $ride);

        return [
            'driver_id' => $driver->id,
            'ride_id' => $ride->id,
            'latitude' => $driver->latitude !== null ? (float) $driver->latitude : null,
            'longitude' => $driver->longitude !== null ? (float) $driver->longitude : null,
            'presence' => $driver->presenceStatus(),
            'last_seen_at' => $driver->last_seen_at?->toIso8601String(),
            'distance_km' => $distance['distance_km'],
            'eta_minutes' => $distance['eta_minutes'],
        ];
    }
}
