<?php

namespace Tests\Feature;

use App\Enums\DriverStatus;
use App\Enums\RideOfferStatus;
use App\Enums\RideStatus;
use App\Events\RideOfferAccepted;
use App\Models\Driver;
use App\Models\Ride;
use App\Models\RideOffer;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RideOfferTest extends TestCase
{
    use RefreshDatabase;

    public function test_driver_accepts_offer_first_wins(): void
    {
        Event::fake([RideOfferAccepted::class]);

        $client = User::factory()->create();
        $driverUser = User::factory()->create();
        $otherDriverUser = User::factory()->create();

        $driver = Driver::factory()->create([
            'user_id' => $driverUser->id,
            'is_available' => true,
            'status' => DriverStatus::Online,
            'latitude' => 0.4162,
            'longitude' => 9.4673,
        ]);

        $otherDriver = Driver::factory()->create([
            'user_id' => $otherDriverUser->id,
            'is_available' => true,
            'status' => DriverStatus::Online,
            'latitude' => 0.4163,
            'longitude' => 9.4674,
        ]);

        Vehicle::factory()->create(['driver_id' => $driver->id]);
        Vehicle::factory()->create(['driver_id' => $otherDriver->id]);

        $ride = Ride::factory()->create([
            'client_id' => $client->id,
            'status' => RideStatus::Searching,
            'driver_id' => null,
            'proposed_price' => 5000,
            'pickup_label' => 'Carrefour STFO',
            'destination_label' => 'Sni owendo',
        ]);

        $winningOffer = RideOffer::query()->create([
            'ride_id' => $ride->id,
            'driver_id' => $driver->id,
            'status' => RideOfferStatus::Pending,
            'offered_price' => 5000,
            'distance_to_pickup_km' => 0.1,
            'dispatch_score' => 0.9,
            'radius_wave' => '0-1km',
            'expires_at' => now()->addMinutes(5),
        ]);

        RideOffer::query()->create([
            'ride_id' => $ride->id,
            'driver_id' => $otherDriver->id,
            'status' => RideOfferStatus::Pending,
            'offered_price' => 5000,
            'distance_to_pickup_km' => 0.2,
            'dispatch_score' => 0.8,
            'radius_wave' => '0-1km',
            'expires_at' => now()->addMinutes(5),
        ]);

        Sanctum::actingAs($driverUser);

        $response = $this->postJson("/api/rides/{$ride->id}/offers/{$winningOffer->id}/accept");

        $response->assertOk()
            ->assertJsonPath('data.status', RideStatus::Accepted->value)
            ->assertJsonPath('data.driver_id', $driver->id)
            ->assertJsonPath('data.agreed_price', 5000);

        $this->assertDatabaseHas('ride_offers', [
            'id' => $winningOffer->id,
            'status' => RideOfferStatus::Accepted->value,
        ]);

        $this->assertDatabaseHas('ride_offers', [
            'ride_id' => $ride->id,
            'driver_id' => $otherDriver->id,
            'status' => RideOfferStatus::Expired->value,
        ]);

        $driver->refresh();
        $this->assertFalse($driver->is_available);

        Event::assertDispatched(RideOfferAccepted::class);
    }

    public function test_driver_can_reject_offer_and_ride_stays_searching(): void
    {
        $client = User::factory()->create();
        $driverUser = User::factory()->create();

        $driver = Driver::factory()->create([
            'user_id' => $driverUser->id,
            'is_available' => true,
            'status' => DriverStatus::Online,
        ]);

        $ride = Ride::factory()->create([
            'client_id' => $client->id,
            'status' => RideStatus::Searching,
            'driver_id' => null,
            'proposed_price' => 4000,
        ]);

        $offer = RideOffer::query()->create([
            'ride_id' => $ride->id,
            'driver_id' => $driver->id,
            'status' => RideOfferStatus::Pending,
            'offered_price' => 4000,
            'distance_to_pickup_km' => 0.5,
            'dispatch_score' => 0.7,
            'radius_wave' => '0-1km',
            'expires_at' => now()->addMinutes(5),
        ]);

        Sanctum::actingAs($driverUser);

        $response = $this->postJson("/api/rides/{$ride->id}/offers/{$offer->id}/reject");

        $response->assertOk()
            ->assertJsonPath('data.status', RideOfferStatus::Rejected->value);

        $this->assertDatabaseHas('rides', [
            'id' => $ride->id,
            'status' => RideStatus::Searching->value,
            'driver_id' => null,
        ]);
    }

    public function test_driver_lists_pending_offers(): void
    {
        $client = User::factory()->create();
        $driverUser = User::factory()->create();

        $driver = Driver::factory()->create([
            'user_id' => $driverUser->id,
            'is_available' => true,
            'status' => DriverStatus::Online,
        ]);

        $ride = Ride::factory()->create([
            'client_id' => $client->id,
            'status' => RideStatus::Searching,
            'proposed_price' => 3000,
            'pickup_label' => 'Test pickup',
            'destination_label' => 'Test destination',
        ]);

        RideOffer::query()->create([
            'ride_id' => $ride->id,
            'driver_id' => $driver->id,
            'status' => RideOfferStatus::Pending,
            'offered_price' => 3000,
            'distance_to_pickup_km' => 0.3,
            'dispatch_score' => 0.75,
            'radius_wave' => '0-1km',
            'expires_at' => now()->addMinutes(5),
        ]);

        Sanctum::actingAs($driverUser);

        $response = $this->getJson('/api/rides/offers/current');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }
}
