<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLiveDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_fetch_live_dashboard_json(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->getJson('/admin/live/dashboard')
            ->assertOk()
            ->assertJsonStructure([
                'stats' => ['total_drivers', 'online_drivers', 'active_rides', 'completed_rides'],
                'recent_rides',
                'refreshed_at',
            ]);
    }

    public function test_admin_can_fetch_live_map_drivers_json(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->getJson('/admin/live/map')
            ->assertOk()
            ->assertJsonStructure(['drivers', 'refreshed_at']);
    }

    public function test_guest_cannot_access_live_endpoints(): void
    {
        $this->getJson('/admin/live/dashboard')->assertUnauthorized();
    }
}
