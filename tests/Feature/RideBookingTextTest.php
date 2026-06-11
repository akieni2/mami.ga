<?php

namespace Tests\Feature;

use App\Enums\BookingType;
use App\Enums\PaymentMethod;
use App\Enums\RideStatus;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RideBookingTextTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_create_text_only_ride_search(): void
    {
        $client = User::factory()->create(['is_admin' => false]);
        Sanctum::actingAs($client);

        $response = $this->postJson('/api/rides/request', [
            'pickup_label' => 'Lalala, rond-point Total',
            'destination_label' => 'Nzeng-Ayong, marché',
            'proposed_price' => 3000,
            'payment_method' => 'cash',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'searching')
            ->assertJsonPath('data.driver_id', null)
            ->assertJsonPath('data.pickup_label', 'Lalala, rond-point Total')
            ->assertJsonPath('data.destination_label', 'Nzeng-Ayong, marché')
            ->assertJsonPath('data.proposed_price', 3000)
            ->assertJsonPath('data.payment_method', 'cash')
            ->assertJsonPath('data.booking_type', 'immediate')
            ->assertJsonPath('data.pickup_latitude', null)
            ->assertJsonPath('data.destination_latitude', null);

        $this->assertDatabaseHas('rides', [
            'client_id' => $client->id,
            'status' => RideStatus::Searching->value,
            'driver_id' => null,
            'booking_type' => BookingType::Immediate->value,
            'pickup_label' => 'Lalala, rond-point Total',
            'destination_label' => 'Nzeng-Ayong, marché',
            'proposed_price' => 3000,
            'payment_method' => PaymentMethod::Cash->value,
        ]);
    }

    public function test_text_booking_with_coordinates_sets_suggested_price(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/rides/request', [
            'pickup_label' => 'Libreville centre',
            'destination_label' => 'Akébé',
            'proposed_price' => 2500,
            'payment_method' => 'airtel_money',
            'pickup_latitude' => 0.4162,
            'pickup_longitude' => 9.4673,
            'destination_latitude' => 0.3900,
            'destination_longitude' => 9.4500,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.payment_method', 'airtel_money')
            ->assertJsonPath('data.suggested_price', fn ($value) => $value > 0)
            ->assertJsonPath('data.distance_km', fn ($value) => $value > 0);
    }

    public function test_text_booking_rejects_invalid_proposed_price(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/rides/request', [
            'pickup_label' => 'Lalala',
            'destination_label' => 'Glass',
            'proposed_price' => 100,
            'payment_method' => 'cash',
        ])->assertStatus(422);
    }

    public function test_text_booking_rejects_short_labels(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/rides/request', [
            'pickup_label' => 'AB',
            'destination_label' => 'Nzeng-Ayong',
            'proposed_price' => 3000,
            'payment_method' => 'cash',
        ])->assertStatus(422);
    }

    public function test_legacy_gps_request_still_works_without_labels(): void
    {
        $client = User::factory()->create();
        Sanctum::actingAs($client);

        $this->postJson('/api/rides/request', [
            'pickup_latitude' => 0.4162,
            'pickup_longitude' => 9.4673,
            'destination_latitude' => 0.4180,
            'destination_longitude' => 9.4690,
        ])->assertStatus(422);
    }
}
