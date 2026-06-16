<?php

namespace Tests\Feature;

use App\Enums\DriverStatus;
use App\Enums\RideStatus;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\RideDispatchEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RideDispatchGpsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['mami.dispatch_v2_enabled' => true]);
    }

    public function test_dispatch_skipped_without_client_pickup_gps(): void
    {
        $client = User::factory()->create();
        Sanctum::actingAs($client);

        $response = $this->postJson('/api/rides/request', [
            'pickup_label' => 'Marché Mont-Bouët',
            'destination_label' => 'Aéroport',
            'proposed_price' => 5000,
            'payment_method' => 'cash',
        ]);

        $response->assertCreated();

        $rideId = $response->json('data.id');

        $this->assertDatabaseHas('rides', [
            'id' => $rideId,
            'status' => RideStatus::Searching->value,
            'pickup_latitude' => null,
        ]);

        $this->assertDatabaseMissing('ride_offers', [
            'ride_id' => $rideId,
        ]);
    }

    public function test_stale_driver_gps_is_not_offered(): void
    {
        $client = User::factory()->create();
        $driver = Driver::factory()->create([
            'is_available' => true,
            'status' => DriverStatus::Online,
            'latitude' => 0.4162,
            'longitude' => 9.4673,
            'last_gps_at' => now()->subMinutes(5),
        ]);

        Vehicle::factory()->create(['driver_id' => $driver->id]);

        $ride = \App\Models\Ride::factory()->create([
            'client_id' => $client->id,
            'status' => RideStatus::Searching,
            'driver_id' => null,
            'pickup_latitude' => 0.4162,
            'pickup_longitude' => 9.4673,
            'proposed_price' => 5000,
            'dispatch_started_at' => null,
            'dispatch_expires_at' => now()->addHours(2),
        ]);

        app(RideDispatchEngine::class)->start($ride);

        $this->assertDatabaseMissing('ride_offers', [
            'ride_id' => $ride->id,
            'driver_id' => $driver->id,
        ]);
    }
}
