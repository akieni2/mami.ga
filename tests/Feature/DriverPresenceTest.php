<?php

namespace Tests\Feature;

use App\Enums\DriverStatus;
use App\Enums\RideStatus;
use App\Models\Driver;
use App\Models\Ride;
use App\Models\User;
use App\Services\DriverPresenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DriverPresenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_gps_update_sets_last_seen_and_online_presence(): void
    {
        $driverUser = User::factory()->create();
        $driver = Driver::factory()->create([
            'user_id' => $driverUser->id,
            'is_available' => true,
            'status' => DriverStatus::Online,
            'last_seen_at' => null,
        ]);

        Sanctum::actingAs($driverUser);

        $this->postJson('/api/drivers/location/update', [
            'latitude' => 0.4162,
            'longitude' => 9.4673,
        ])->assertOk()
            ->assertJsonPath('data.presence', 'online');

        $driver->refresh();
        $this->assertNotNull($driver->last_seen_at);
        $this->assertSame('online', $driver->presenceStatus());
    }

    public function test_driver_on_active_ride_is_busy(): void
    {
        $driver = Driver::factory()->create([
            'is_available' => false,
            'status' => DriverStatus::OnRide,
            'last_seen_at' => now(),
        ]);

        Ride::factory()->create([
            'driver_id' => $driver->id,
            'status' => RideStatus::Accepted,
        ]);

        $this->assertSame('busy', $driver->fresh()->presenceStatus());
    }

    public function test_stale_driver_is_marked_offline(): void
    {
        $driver = Driver::factory()->create([
            'is_available' => true,
            'status' => DriverStatus::Online,
            'last_seen_at' => Carbon::now()->subMinutes(10),
        ]);

        $count = app(DriverPresenceService::class)->markStaleDriversOffline();

        $this->assertSame(1, $count);
        $driver->refresh();
        $this->assertSame(DriverStatus::Offline, $driver->status);
        $this->assertFalse($driver->is_available);
    }
}
