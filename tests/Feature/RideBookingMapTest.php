<?php

namespace Tests\Feature;

use App\Enums\LocationSource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RideBookingMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_text_only_booking_sets_text_sources(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/rides/request', [
            'pickup_label' => 'Carrefour STFO',
            'destination_label' => 'Sni owendo',
            'proposed_price' => 5000,
            'payment_method' => 'cash',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.pickup_source', 'text')
            ->assertJsonPath('data.destination_source', 'text');
    }

    public function test_hybrid_booking_when_text_and_coordinates(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/rides/request', [
            'pickup_label' => 'Carrefour STFO',
            'destination_label' => 'Sni owendo',
            'proposed_price' => 5000,
            'payment_method' => 'cash',
            'pickup_latitude' => 0.4162,
            'pickup_longitude' => 9.4673,
            'destination_latitude' => 0.3900,
            'destination_longitude' => 9.4500,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.pickup_source', 'hybrid')
            ->assertJsonPath('data.destination_source', 'hybrid')
            ->assertJsonPath('data.suggested_price', fn ($v) => $v > 0)
            ->assertJsonPath('data.distance_km', fn ($v) => $v > 0)
            ->assertJsonPath('data.duration_minutes', fn ($v) => $v > 0);
    }

    public function test_map_only_booking_when_coordinate_labels(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/rides/request', [
            'pickup_label' => '0.4162, 9.4673',
            'destination_label' => '0.3900, 9.4500',
            'proposed_price' => 4000,
            'payment_method' => 'moov_money',
            'pickup_latitude' => 0.4162,
            'pickup_longitude' => 9.4673,
            'destination_latitude' => 0.3900,
            'destination_longitude' => 9.4500,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.pickup_source', 'map')
            ->assertJsonPath('data.destination_source', 'map');

        $this->assertDatabaseHas('rides', [
            'pickup_source' => LocationSource::Map->value,
            'destination_source' => LocationSource::Map->value,
        ]);
    }
}
