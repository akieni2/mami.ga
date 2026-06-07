<?php

namespace Tests\Feature;

use App\Events\RideAssigned;
use App\Events\RideRequested;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReverbBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_ride_requested_broadcasts_on_reverb_private_channels(): void
    {
        $ride = Ride::factory()->create();
        $event = new RideRequested($ride);
        $names = collect($event->broadcastOn())->map(fn ($c) => $c->name)->all();

        $this->assertContains('private-ride-'.$ride->id, $names);
        $this->assertContains('private-user-'.$ride->client_id, $names);
        $this->assertContains('private-driver-'.$ride->driver_id, $names);
    }

    public function test_client_can_authorize_private_ride_channel(): void
    {
        $client = User::factory()->create();
        $ride = Ride::factory()->create(['client_id' => $client->id]);

        Sanctum::actingAs($client);

        $response = $this->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-ride-'.$ride->id,
        ]);

        $response->assertOk();
    }

    public function test_ride_assigned_event_uses_expected_broadcast_name(): void
    {
        $ride = Ride::factory()->create();
        $event = new RideAssigned($ride);

        $this->assertSame('RideAssigned', $event->broadcastAs());
    }
}
