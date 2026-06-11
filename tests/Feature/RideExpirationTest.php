<?php

namespace Tests\Feature;

use App\Enums\RideOfferStatus;
use App\Enums\RideStatus;
use App\Events\RideSearchExpired;
use App\Jobs\ExpireRideSearchJob;
use App\Models\Ride;
use App\Models\RideOffer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RideExpirationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['mami.dispatch_v2_enabled' => true]);
    }

    public function test_expire_ride_search_job_marks_ride_expired(): void
    {
        Event::fake([RideSearchExpired::class]);

        $client = User::factory()->create();

        $ride = Ride::factory()->create([
            'client_id' => $client->id,
            'status' => RideStatus::Searching,
            'driver_id' => null,
            'dispatch_expires_at' => now()->subMinute(),
            'dispatch_started_at' => now()->subHours(2),
        ]);

        $offer = RideOffer::query()->create([
            'ride_id' => $ride->id,
            'driver_id' => \App\Models\Driver::factory()->create()->id,
            'status' => RideOfferStatus::Pending,
            'offered_price' => 5000,
            'distance_to_pickup_km' => 1.0,
            'dispatch_score' => 0.5,
            'radius_wave' => '0-1km',
            'expires_at' => now()->addMinutes(5),
        ]);

        ExpireRideSearchJob::dispatchSync();

        $this->assertDatabaseHas('rides', [
            'id' => $ride->id,
            'status' => RideStatus::Expired->value,
        ]);

        $this->assertDatabaseHas('ride_offers', [
            'id' => $offer->id,
            'status' => RideOfferStatus::Expired->value,
        ]);

        Event::assertDispatched(RideSearchExpired::class);
    }
}
