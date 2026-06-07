<?php

namespace Tests\Feature;

use App\Enums\RideStatus;
use App\Models\Driver;
use App\Models\Ride;
use App\Services\EstimatedArrivalService;
use App\Services\RouteService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteAndEtaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_route_service_returns_straight_line_coordinates(): void
    {
        $service = app(RouteService::class);

        $route = $service->route(0.4160, 9.4670, 0.4200, 9.4800);

        $this->assertSame('straight_line', $route['provider']);
        $this->assertGreaterThan(2, count($route['coordinates']));
        $this->assertEqualsWithDelta(0.4160, $route['coordinates'][0]['latitude'], 0.0001);
        $this->assertEqualsWithDelta(0.4200, $route['coordinates'][array_key_last($route['coordinates'])]['latitude'], 0.0001);
    }

    public function test_estimated_arrival_service_between_points(): void
    {
        $service = app(EstimatedArrivalService::class);

        $eta = $service->betweenPoints(0.4160, 9.4670, 0.4200, 9.4800);

        $this->assertGreaterThan(0, $eta['distance_km']);
        $this->assertGreaterThanOrEqual(1, $eta['eta_minutes']);
    }

    public function test_tracking_snapshot_includes_route_and_estimated_arrival(): void
    {
        $driver = Driver::factory()->create([
            'latitude' => 0.4162,
            'longitude' => 9.4673,
        ]);

        $ride = Ride::factory()->create([
            'driver_id' => $driver->id,
            'status' => RideStatus::Accepted,
            'pickup_latitude' => 0.4160,
            'pickup_longitude' => 9.4670,
        ]);

        $snapshot = app(\App\Services\RideTrackingService::class)->snapshot($ride);

        $this->assertArrayHasKey('route', $snapshot);
        $this->assertSame('straight_line', $snapshot['route']['provider']);
        $this->assertArrayHasKey('estimated_arrival', $snapshot['tracking']);
    }
}
