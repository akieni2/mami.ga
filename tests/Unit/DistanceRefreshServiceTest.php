<?php

namespace Tests\Unit;

use App\Enums\RideStatus;
use App\Models\Driver;
use App\Models\Ride;
use App\Services\DistanceRefreshService;
use Tests\TestCase;

class DistanceRefreshServiceTest extends TestCase
{
    public function test_eta_is_calculated_from_distance(): void
    {
        $service = new DistanceRefreshService;

        $eta = $service->estimateEtaMinutes(5.0);

        $this->assertGreaterThanOrEqual(1, $eta);
    }

    public function test_tracking_target_switches_to_destination_when_started(): void
    {
        $service = new DistanceRefreshService;
        $driver = new Driver([
            'latitude' => 0.4162,
            'longitude' => 9.4673,
        ]);
        $ride = new Ride([
            'status' => RideStatus::Started,
            'pickup_latitude' => 0.4160,
            'pickup_longitude' => 9.4670,
            'destination_latitude' => 0.5000,
            'destination_longitude' => 9.6000,
        ]);

        $metrics = $service->refreshForRide($driver, $ride);

        $this->assertSame(0.5, $metrics['target_latitude']);
        $this->assertSame(9.6, $metrics['target_longitude']);
    }
}
