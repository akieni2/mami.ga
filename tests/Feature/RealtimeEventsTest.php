<?php

namespace Tests\Feature;

use App\Enums\DriverStatus;
use App\Enums\RideEventType;
use App\Events\DriverArrived;
use App\Events\DriverLocationUpdated;
use App\Events\RideAccepted;
use App\Events\RideCompleted;
use App\Events\RideRequested;
use App\Events\RideStarted;
use App\Models\Driver;
use App\Models\Ride;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RealtimeEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_ride_lifecycle_dispatches_realtime_events_and_audit_records(): void
    {
        Event::fake([
            RideRequested::class,
            RideAccepted::class,
            DriverArrived::class,
            RideStarted::class,
            RideCompleted::class,
        ]);

        $client = User::factory()->create();
        $driverUser = User::factory()->create(['email' => 'driver-events@mami.ga']);
        $driver = Driver::factory()->create([
            'user_id' => $driverUser->id,
            'is_available' => true,
            'status' => DriverStatus::Online,
            'latitude' => 0.4162,
            'longitude' => 9.4673,
        ]);
        Vehicle::factory()->create(['driver_id' => $driver->id]);

        Sanctum::actingAs($client);
        $rideId = $this->postJson('/api/rides/request', [
            'pickup_latitude' => 0.4160,
            'pickup_longitude' => 9.4670,
            'destination_latitude' => 0.4200,
            'destination_longitude' => 9.4800,
        ])->json('data.id');

        Event::assertDispatched(RideRequested::class);
        $this->assertDatabaseHas('ride_events', [
            'ride_id' => $rideId,
            'event_type' => RideEventType::RideRequested->value,
        ]);

        Sanctum::actingAs($driverUser);
        $this->postJson("/api/rides/{$rideId}/accept")->assertOk();
        Event::assertDispatched(RideAccepted::class);

        $this->postJson("/api/rides/{$rideId}/arrived")->assertOk();
        Event::assertDispatched(DriverArrived::class);

        $this->postJson("/api/rides/{$rideId}/start")->assertOk();
        Event::assertDispatched(RideStarted::class);

        $this->postJson("/api/rides/{$rideId}/complete")->assertOk();
        Event::assertDispatched(RideCompleted::class);

        $this->assertDatabaseCount('ride_events', 5);
    }

    public function test_driver_location_update_dispatches_broadcast_event(): void
    {
        Event::fake([DriverLocationUpdated::class]);

        $driverUser = User::factory()->create();
        $driver = Driver::factory()->create([
            'user_id' => $driverUser->id,
            'status' => DriverStatus::Online,
        ]);
        Vehicle::factory()->create(['driver_id' => $driver->id]);

        Sanctum::actingAs($driverUser);

        $this->postJson('/api/drivers/location/update', [
            'latitude' => 0.4170,
            'longitude' => 9.4680,
        ])->assertOk();

        Event::assertDispatched(DriverLocationUpdated::class);
    }

    public function test_broadcast_event_uses_firebase_friendly_envelope(): void
    {
        $ride = Ride::factory()->create();
        $event = new RideRequested($ride);
        $payload = $event->broadcastWith();

        $this->assertArrayHasKey('event', $payload);
        $this->assertArrayHasKey('payload', $payload);
        $this->assertArrayHasKey('occurred_at', $payload);
        $this->assertSame('RideRequested', $payload['event']);
    }
}
