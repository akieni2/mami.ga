<?php

namespace Tests\Feature;

use App\Enums\DriverStatus;
use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDriverDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_driver_detail_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $driverUser = User::factory()->create([
            'name' => 'Jean Chauffeur',
            'email' => 'jean.driver@test.com',
            'phone' => '+241061111111',
        ]);

        $driver = Driver::factory()->create([
            'user_id' => $driverUser->id,
            'status' => DriverStatus::Online,
            'latitude' => 0.4162,
            'longitude' => 9.4673,
            'last_seen_at' => now(),
        ]);

        Vehicle::factory()->create([
            'driver_id' => $driver->id,
            'brand' => 'Toyota',
            'model' => 'Corolla',
            'plate_number' => 'GA-123-AB',
        ]);

        $this->actingAs($admin)
            ->get('/admin/drivers/'.$driver->id)
            ->assertOk()
            ->assertSee('Jean Chauffeur')
            ->assertSee('jean.driver@test.com')
            ->assertSee('GA-123-AB')
            ->assertSee('Voir en temps réel')
            ->assertSee('driver-detail-map');
    }

    public function test_admin_can_view_driver_live_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $driverUser = User::factory()->create(['name' => 'Live Driver']);
        $driver = Driver::factory()->create([
            'user_id' => $driverUser->id,
            'latitude' => 0.4162,
            'longitude' => 9.4673,
        ]);

        Vehicle::factory()->create([
            'driver_id' => $driver->id,
            'brand' => 'Toyota',
            'model' => 'Yaris',
            'plate_number' => 'GA-999-ZZ',
        ]);

        $this->actingAs($admin)
            ->get('/admin/drivers/'.$driver->id.'/live')
            ->assertOk()
            ->assertSee('driver-live-map')
            ->assertSee('mamiDriverLiveMeta')
            ->assertSee('Live Driver')
            ->assertSee('0.4162')
            ->assertSee('data-live-endpoint');
    }

    public function test_admin_can_fetch_single_driver_live_json(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $driver = Driver::factory()->create([
            'latitude' => 0.4162,
            'longitude' => 9.4673,
        ]);

        $this->actingAs($admin)
            ->getJson('/admin/live/drivers/'.$driver->id)
            ->assertOk()
            ->assertJsonStructure([
                'driver' => [
                    'id',
                    'name',
                    'email',
                    'phone',
                    'presence',
                    'status',
                    'latitude',
                    'longitude',
                    'plate_number',
                    'last_seen_at',
                ],
                'refreshed_at',
            ])
            ->assertJsonPath('driver.id', $driver->id);
    }

    public function test_guest_cannot_access_driver_detail(): void
    {
        $driver = Driver::factory()->create();

        $this->get('/admin/drivers/'.$driver->id)->assertRedirect('/login');
        $this->get('/admin/drivers/'.$driver->id.'/live')->assertRedirect('/login');
    }

    public function test_non_admin_cannot_access_driver_detail(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $driver = Driver::factory()->create();

        $this->actingAs($user)->get('/admin/drivers/'.$driver->id)->assertForbidden();
        $this->actingAs($user)->get('/admin/drivers/'.$driver->id.'/live')->assertForbidden();
    }
}
