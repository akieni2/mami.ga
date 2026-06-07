<?php

namespace Tests\Feature;

use App\Enums\RideStatus;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminRidesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_rides_by_status(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $client = User::factory()->create();

        Ride::factory()->create(['client_id' => $client->id, 'status' => RideStatus::Pending]);
        Ride::factory()->create(['client_id' => $client->id, 'status' => RideStatus::Completed]);

        $this->actingAs($admin)
            ->get('/admin/rides?status=pending')
            ->assertOk()
            ->assertSee('Pending')
            ->assertDontSee('completed');
    }

    public function test_admin_can_view_ride_detail(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $client = User::factory()->create(['name' => 'Client Test']);
        $ride = Ride::factory()->create(['client_id' => $client->id]);

        $this->actingAs($admin)
            ->get('/admin/rides/'.$ride->id)
            ->assertOk()
            ->assertSee('Client Test')
            ->assertSee('Informations course');
    }
}
