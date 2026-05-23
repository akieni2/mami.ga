<?php

namespace Tests\Feature;

use App\Enums\DriverStatus;
use App\Enums\RideEventType;
use App\Enums\RideStatus;
use App\Models\Driver;
use App\Models\Ride;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RideTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_fetch_ride_tracking_snapshot(): void
    {
        $client = User::factory()->create();
        $driver = Driver::factory()->create([
            'status' => DriverStatus::OnRide,
            'is_available' => false,
            'latitude' => 0.4162,
            'longitude' => 9.4673,
            'last_seen_at' => now(),
        ]);
        Vehicle::factory()->create(['driver_id' => $driver->id]);

        $ride = Ride::factory()->create([
            'client_id' => $client->id,
            'driver_id' => $driver->id,
            'status' => RideStatus::Accepted,
            'pickup_latitude' => 0.4160,
            'pickup_longitude' => 9.4670,
            'destination_latitude' => 0.4200,
            'destination_longitude' => 9.4800,
        ]);

        $ride->events()->create([
            'driver_id' => $driver->id,
            'event_type' => RideEventType::RideAccepted,
            'payload' => [],
        ]);

        Sanctum::actingAs($client);

        $response = $this->getJson("/api/rides/{$ride->id}/tracking");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.ride.id', $ride->id)
            ->assertJsonPath('data.driver.id', $driver->id)
            ->assertJsonStructure([
                'data' => [
                    'ride',
                    'driver',
                    'tracking' => ['distance_km', 'eta_minutes'],
                    'events',
                ],
            ]);

        $this->assertNotNull($response->json('data.tracking.distance_km'));
        $this->assertNotNull($response->json('data.tracking.eta_minutes'));
    }

    public function test_client_can_fetch_driver_live_location_during_ride(): void
    {
        $client = User::factory()->create();
        $driver = Driver::factory()->create([
            'status' => DriverStatus::OnRide,
            'latitude' => 0.4162,
            'longitude' => 9.4673,
            'last_seen_at' => now(),
        ]);

        Ride::factory()->create([
            'client_id' => $client->id,
            'driver_id' => $driver->id,
            'status' => RideStatus::Accepted,
            'pickup_latitude' => 0.4160,
            'pickup_longitude' => 9.4670,
        ]);

        Sanctum::actingAs($client);

        $this->getJson("/api/drivers/{$driver->id}/live-location")
            ->assertOk()
            ->assertJsonPath('data.driver_id', $driver->id)
            ->assertJsonStructure(['data' => ['latitude', 'longitude', 'distance_km', 'eta_minutes']]);
    }
}
