<?php

namespace Tests\Feature;

use App\Enums\RideStatus;
use App\Models\Driver;
use App\Models\Ride;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DriverRideApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_can_fetch_current_ride_with_distance(): void
    {
        $driverUser = User::factory()->create();
        $client = User::factory()->create();

        $driver = Driver::factory()->create([
            'user_id' => $driverUser->id,
            'latitude' => 0.4162,
            'longitude' => 9.4673,
        ]);

        Vehicle::factory()->create(['driver_id' => $driver->id]);

        $ride = Ride::factory()->create([
            'client_id' => $client->id,
            'driver_id' => $driver->id,
            'status' => RideStatus::Pending,
            'pickup_latitude' => 0.4160,
            'pickup_longitude' => 9.4670,
        ]);

        Sanctum::actingAs($driverUser);

        $response = $this->getJson('/api/rides/current');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $ride->id)
            ->assertJsonPath('data.status', RideStatus::Pending->value)
            ->assertJsonStructure(['data' => ['distance_to_pickup_km']]);
    }

    public function test_driver_current_returns_null_when_no_active_ride(): void
    {
        $driverUser = User::factory()->create();
        Driver::factory()->create(['user_id' => $driverUser->id]);

        Sanctum::actingAs($driverUser);

        $this->getJson('/api/rides/current')
            ->assertOk()
            ->assertJsonPath('data', null);
    }

    public function test_driver_can_reject_pending_ride(): void
    {
        $driverUser = User::factory()->create();
        $client = User::factory()->create();

        $driver = Driver::factory()->create([
            'user_id' => $driverUser->id,
            'is_available' => false,
        ]);

        $ride = Ride::factory()->create([
            'client_id' => $client->id,
            'driver_id' => $driver->id,
            'status' => RideStatus::Pending,
        ]);

        Sanctum::actingAs($driverUser);

        $response = $this->postJson("/api/rides/{$ride->id}/reject");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', RideStatus::Cancelled->value);

        $driver->refresh();
        $this->assertTrue($driver->is_available);
        $this->assertNull($ride->fresh()->driver_id);
    }
}
