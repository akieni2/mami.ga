<?php

namespace Database\Factories;

use App\Enums\DriverStatus;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Driver>
 */
class DriverFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'license_number' => strtoupper(fake()->unique()->bothify('GA-####-??')),
            'is_available' => true,
            'status' => DriverStatus::Online,
            'latitude' => fake()->latitude(-1.5, 0.5),
            'longitude' => fake()->longitude(8.5, 12.5),
            'rating' => fake()->randomFloat(2, 4, 5),
        ];
    }

    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => true,
            'status' => DriverStatus::Online,
        ]);
    }

    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => false,
            'status' => DriverStatus::Offline,
        ]);
    }
}
