<?php

namespace Database\Factories;

use App\Enums\RideStatus;
use App\Models\Driver;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ride>
 */
class RideFactory extends Factory
{
    public function definition(): array
    {
        $pickupLat = fake()->latitude(-1.5, 0.5);
        $pickupLng = fake()->longitude(8.5, 12.5);

        return [
            'client_id' => User::factory(),
            'driver_id' => Driver::factory(),
            'pickup_latitude' => $pickupLat,
            'pickup_longitude' => $pickupLng,
            'destination_latitude' => $pickupLat + fake()->randomFloat(4, 0.01, 0.05),
            'destination_longitude' => $pickupLng + fake()->randomFloat(4, 0.01, 0.05),
            'status' => RideStatus::Pending,
            'estimated_price' => fake()->randomFloat(2, 500, 5000),
            'started_at' => null,
            'completed_at' => null,
        ];
    }
}
