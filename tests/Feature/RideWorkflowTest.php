<?php

namespace Tests\Feature;

use App\Enums\DriverStatus;
use App\Enums\RideStatus;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RideWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_request_ride_and_driver_accepts_and_completes(): void
    {
        $client = User::factory()->create();
        $driverUser = User::factory()->create(['email' => 'driver@example.com']);

        $driver = Driver::factory()->create([
            'user_id' => $driverUser->id,
            'is_available' => true,
            'status' => DriverStatus::Online->value,
            'latitude' => 0.4162,
            'longitude' => 9.4673,
            'last_seen_at' => now(),
        ]);

        Vehicle::factory()->create(['driver_id' => $driver->id]);

        Sanctum::actingAs($client);

        $requestResponse = $this->postJson('/api/rides/request', [
            'pickup_latitude' => 0.4160,
            'pickup_longitude' => 9.4670,
            'destination_latitude' => 0.4200,
            'destination_longitude' => 9.4800,
        ]);

        $requestResponse->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', RideStatus::Pending->value)
            ->assertJsonPath('data.driver_id', $driver->id);

        $rideId = $requestResponse->json('data.id');

        $driver->refresh();
        $this->assertFalse($driver->is_available);

        Sanctum::actingAs($driverUser);

        $acceptResponse = $this->postJson("/api/rides/{$rideId}/accept");
        $acceptResponse->assertOk()
            ->assertJsonPath('data.status', RideStatus::Accepted->value);

        $startResponse = $this->postJson("/api/rides/{$rideId}/start");
        $startResponse->assertOk()
            ->assertJsonPath('data.status', RideStatus::Started->value);

        $completeResponse = $this->postJson("/api/rides/{$rideId}/complete");
        $completeResponse->assertOk()
            ->assertJsonPath('data.status', RideStatus::Completed->value);

        $driver->refresh();
        $this->assertTrue($driver->is_available);
        $this->assertSame(DriverStatus::Online, $driver->status);
    }

    public function test_request_ride_fails_when_no_drivers_available(): void
    {
        $client = User::factory()->create();
        Sanctum::actingAs($client);

        $response = $this->postJson('/api/rides/request', [
            'pickup_latitude' => 0.4160,
            'pickup_longitude' => 9.4670,
            'destination_latitude' => 0.4200,
            'destination_longitude' => 9.4800,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
