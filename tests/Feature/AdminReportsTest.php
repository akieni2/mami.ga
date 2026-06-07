<?php

namespace Tests\Feature;

use App\Enums\RideStatus;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_reports_with_periods(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $client = User::factory()->create();

        Ride::factory()->create([
            'client_id' => $client->id,
            'status' => RideStatus::Completed,
            'estimated_price' => 5000,
            'completed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/admin/reports?period=day')
            ->assertOk()
            ->assertSee('Rapports')
            ->assertSee('5');

        $this->actingAs($admin)
            ->get('/admin/reports?period=week')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/reports?period=month')
            ->assertOk();
    }
}
