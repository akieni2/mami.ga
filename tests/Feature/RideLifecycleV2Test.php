<?php

namespace Tests\Feature;

use App\Enums\DriverStatus;
use App\Enums\RideEventType;
use App\Enums\RideOfferStatus;
use App\Enums\RideStatus;
use App\Events\DriverArrived;
use App\Events\DriverLocationUpdated;
use App\Events\RideCompleted;
use App\Events\RideOfferAccepted;
use App\Events\RideStarted;
use App\Models\Driver;
use App\Models\Ride;
use App\Models\RideOffer;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RideLifecycleV2Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['mami.dispatch_v2_enabled' => true]);
    }

    public function test_v2_offer_accept_to_complete_lifecycle(): void
    {
        Event::fake([
            RideOfferAccepted::class,
            DriverArrived::class,
            RideStarted::class,
            RideCompleted::class,
        ]);

        $client = User::factory()->create();
        $driverUser = User::factory()->create(['email' => 'driver-lifecycle@mami.ga']);
        $driver = Driver::factory()->create([
            'user_id' => $driverUser->id,
            'is_available' => true,
            'status' => DriverStatus::Online,
            'latitude' => 0.4162,
            'longitude' => 9.4673,
            'last_gps_at' => now(),
        ]);
        Vehicle::factory()->create(['driver_id' => $driver->id]);

        $ride = Ride::factory()->create([
            'client_id' => $client->id,
            'status' => RideStatus::Searching,
            'driver_id' => null,
            'pickup_latitude' => 0.4162,
            'pickup_longitude' => 9.4673,
            'destination_latitude' => 0.4200,
            'destination_longitude' => 9.4800,
            'proposed_price' => 5000,
        ]);

        $offer = RideOffer::query()->create([
            'ride_id' => $ride->id,
            'driver_id' => $driver->id,
            'status' => RideOfferStatus::Pending,
            'offered_price' => 5000,
            'distance_to_pickup_km' => 0.1,
            'dispatch_score' => 0.9,
            'radius_wave' => '1km',
            'expires_at' => now()->addMinutes(5),
        ]);

        Sanctum::actingAs($driverUser);

        $this->postJson("/api/rides/{$ride->id}/offers/{$offer->id}/accept")
            ->assertOk()
            ->assertJsonPath('data.status', RideStatus::Accepted->value);

        Event::assertDispatched(RideOfferAccepted::class);

        $this->postJson("/api/rides/{$ride->id}/arrived")
            ->assertOk()
            ->assertJsonPath('data.status', RideStatus::Arrived->value);

        Event::assertDispatched(DriverArrived::class);

        $this->postJson("/api/rides/{$ride->id}/start")
            ->assertOk()
            ->assertJsonPath('data.status', RideStatus::Started->value);

        Event::assertDispatched(RideStarted::class);

        $this->postJson("/api/rides/{$ride->id}/complete")
            ->assertOk()
            ->assertJsonPath('data.status', RideStatus::Completed->value);

        Event::assertDispatched(RideCompleted::class);

        $driver->refresh();
        $this->assertTrue($driver->is_available);
        $this->assertSame(DriverStatus::Online, $driver->status);

        $this->assertDatabaseHas('ride_events', [
            'ride_id' => $ride->id,
            'event_type' => RideEventType::RideAccepted->value,
        ]);
        $this->assertDatabaseHas('ride_events', [
            'ride_id' => $ride->id,
            'event_type' => RideEventType::DriverArrived->value,
        ]);
        $this->assertDatabaseHas('ride_events', [
            'ride_id' => $ride->id,
            'event_type' => RideEventType::RideStarted->value,
        ]);
        $this->assertDatabaseHas('ride_events', [
            'ride_id' => $ride->id,
            'event_type' => RideEventType::RideCompleted->value,
        ]);
    }

    public function test_driver_gps_update_broadcasts_during_active_ride(): void
    {
        Event::fake([DriverLocationUpdated::class]);

        $client = User::factory()->create();
        $driverUser = User::factory()->create();
        $driver = Driver::factory()->create([
            'user_id' => $driverUser->id,
            'status' => DriverStatus::OnRide,
            'is_available' => false,
            'latitude' => 0.4160,
            'longitude' => 9.4670,
            'last_gps_at' => now(),
        ]);
        Vehicle::factory()->create(['driver_id' => $driver->id]);

        Ride::factory()->create([
            'client_id' => $client->id,
            'driver_id' => $driver->id,
            'status' => RideStatus::Accepted,
            'pickup_latitude' => 0.4162,
            'pickup_longitude' => 9.4673,
            'destination_latitude' => 0.4200,
            'destination_longitude' => 9.4800,
        ]);

        Sanctum::actingAs($driverUser);

        $this->postJson('/api/drivers/location/update', [
            'latitude' => 0.4163,
            'longitude' => 9.4674,
            'accuracy_meters' => 15,
        ])->assertOk();

        Event::assertDispatched(DriverLocationUpdated::class);
    }

    public function test_client_can_fetch_tracking_during_accepted_ride(): void
    {
        $client = User::factory()->create();
        $driver = Driver::factory()->create([
            'is_available' => false,
            'status' => DriverStatus::OnRide,
            'latitude' => 0.4162,
            'longitude' => 9.4673,
            'last_gps_at' => now(),
        ]);

        $ride = Ride::factory()->create([
            'client_id' => $client->id,
            'driver_id' => $driver->id,
            'status' => RideStatus::Accepted,
            'pickup_latitude' => 0.4162,
            'pickup_longitude' => 9.4673,
            'destination_latitude' => 0.4200,
            'destination_longitude' => 9.4800,
        ]);

        Sanctum::actingAs($client);

        $this->getJson("/api/rides/{$ride->id}/tracking")
            ->assertOk()
            ->assertJsonPath('data.ride.id', $ride->id)
            ->assertJsonPath('data.driver.id', $driver->id)
            ->assertJsonStructure([
                'data' => [
                    'ride',
                    'driver',
                    'tracking' => ['distance_km', 'eta_minutes'],
                ],
            ]);
    }
}
