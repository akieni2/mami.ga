<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RideEstimateTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_client_can_estimate_trip(): void
    {
        $client = User::factory()->create(['is_admin' => false]);
        Sanctum::actingAs($client);

        $response = $this->postJson('/api/rides/estimate', [
            'pickup_latitude' => 0.4162,
            'pickup_longitude' => 9.4673,
            'destination_latitude' => 0.3900,
            'destination_longitude' => 9.4500,
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'distance_km',
                    'duration_minutes',
                    'suggested_price',
                    'estimated_price',
                ],
            ]);

        $this->assertGreaterThan(0, $response->json('data.distance_km'));
        $this->assertGreaterThan(0, $response->json('data.suggested_price'));
    }

    public function test_estimate_requires_valid_coordinates(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/rides/estimate', [])
            ->assertStatus(422);
    }

    public function test_app_features_endpoint_returns_v2_flags(): void
    {
        $response = $this->getJson('/api/app/features');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'taxi_v2_enabled',
                    'dispatch_v2_enabled',
                    'ride_base_price',
                    'ride_price_per_km',
                ],
            ]);
    }
}
