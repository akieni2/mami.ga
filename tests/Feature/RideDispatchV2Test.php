<?php

namespace Tests\Feature;

use App\Enums\DriverStatus;
use App\Enums\RideOfferStatus;
use App\Enums\RideStatus;
use App\Events\RideOfferCreated;
use App\Models\Driver;
use App\Models\Ride;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RideDispatchV2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['mami.dispatch_v2_enabled' => true]);
    }

    public function test_text_booking_starts_dispatch_and_creates_offer_for_nearby_driver(): void
    {
        Event::fake([RideOfferCreated::class]);

        $client = User::factory()->create();
        $driverUser = User::factory()->create(['email' => 'driver-dispatch@test.com']);

        $driver = Driver::factory()->create([
            'user_id' => $driverUser->id,
            'is_available' => true,
            'status' => DriverStatus::Online,
            'latitude' => 0.4162,
            'longitude' => 9.4673,
            'last_seen_at' => now(),
            'last_gps_at' => now(),
        ]);

        Vehicle::factory()->create(['driver_id' => $driver->id]);

        Sanctum::actingAs($client);

        $response = $this->postJson('/api/rides/request', [
            'pickup_label' => 'Carrefour STFO',
            'destination_label' => 'Sni owendo',
            'proposed_price' => 5000,
            'payment_method' => 'cash',
            'pickup_latitude' => 0.4162,
            'pickup_longitude' => 9.4673,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', RideStatus::Searching->value)
            ->assertJsonPath('data.driver_id', null);

        $rideId = $response->json('data.id');

        $this->assertDatabaseHas('rides', [
            'id' => $rideId,
            'status' => RideStatus::Searching->value,
        ]);

        $this->assertDatabaseHas('ride_offers', [
            'ride_id' => $rideId,
            'driver_id' => $driver->id,
            'status' => RideOfferStatus::Pending->value,
        ]);

        $this->assertDatabaseHas('ride_dispatch_waves', [
            'ride_id' => $rideId,
            'radius_min_km' => 0,
            'radius_max_km' => 1,
            'drivers_notified' => 1,
        ]);

        Event::assertDispatched(RideOfferCreated::class);
    }

    public function test_offline_driver_is_not_offered(): void
    {
        $client = User::factory()->create();

        Driver::factory()->create([
            'is_available' => true,
            'status' => DriverStatus::Offline,
            'latitude' => 0.4162,
            'longitude' => 9.4673,
        ]);

        Sanctum::actingAs($client);

        $response = $this->postJson('/api/rides/request', [
            'pickup_label' => 'Carrefour STFO',
            'destination_label' => 'Sni owendo',
            'proposed_price' => 5000,
            'payment_method' => 'cash',
            'pickup_latitude' => 0.4162,
            'pickup_longitude' => 9.4673,
        ]);

        $response->assertCreated();

        $rideId = $response->json('data.id');

        $this->assertDatabaseMissing('ride_offers', [
            'ride_id' => $rideId,
        ]);
    }

    public function test_existing_searching_ride_enters_dispatch_on_recovery(): void
    {
        $client = User::factory()->create();
        $driver = Driver::factory()->create([
            'is_available' => true,
            'status' => DriverStatus::Online,
            'latitude' => 0.4162,
            'longitude' => 9.4673,
        ]);

        Vehicle::factory()->create(['driver_id' => $driver->id]);

        $ride = Ride::factory()->create([
            'client_id' => $client->id,
            'status' => RideStatus::Searching,
            'driver_id' => null,
            'pickup_label' => 'Carrefour STFO',
            'destination_label' => 'Sni owendo',
            'pickup_latitude' => 0.4162,
            'pickup_longitude' => 9.4673,
            'proposed_price' => 5000,
            'dispatch_started_at' => null,
            'dispatch_expires_at' => now()->addHours(2),
        ]);

        app(\App\Services\RideDispatchEngine::class)->recoverPendingSearches();

        $this->assertDatabaseHas('rides', [
            'id' => $ride->id,
        ]);

        $ride->refresh();
        $this->assertNotNull($ride->dispatch_started_at);

        $this->assertDatabaseHas('ride_offers', [
            'ride_id' => $ride->id,
            'driver_id' => $driver->id,
        ]);
    }

    public function test_v1_gps_request_blocked_when_dispatch_v2_enabled(): void
    {
        $client = User::factory()->create();
        Sanctum::actingAs($client);

        $response = $this->postJson('/api/rides/request', [
            'pickup_latitude' => 0.4160,
            'pickup_longitude' => 9.4670,
            'destination_latitude' => 0.4200,
            'destination_longitude' => 9.4800,
        ]);

        $response->assertStatus(422);
    }
}
