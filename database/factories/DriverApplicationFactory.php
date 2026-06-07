<?php

namespace Database\Factories;

use App\Enums\DriverApplicationStatus;
use App\Models\DriverApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DriverApplication>
 */
class DriverApplicationFactory extends Factory
{
    protected $model = DriverApplication::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => '+24106'.fake()->numerify('#######'),
            'email' => fake()->unique()->safeEmail(),
            'national_id_number' => 'NID-'.fake()->numerify('########'),
            'driving_license_number' => 'GA-'.fake()->numerify('####').'-'.fake()->lexify('??'),
            'vehicle_brand' => fake()->randomElement(['Toyota', 'Peugeot', 'Hyundai']),
            'vehicle_model' => fake()->word(),
            'vehicle_color' => fake()->safeColorName(),
            'vehicle_year' => (int) fake()->numberBetween(2010, (int) date('Y')),
            'plate_number' => 'GA-'.fake()->numerify('###').'-'.fake()->lexify('??'),
            'vehicle_type' => fake()->randomElement(['sedan', 'suv', 'taxi']),
            'driver_photo_path' => 'driver-applications/placeholder/driver.jpg',
            'license_photo_path' => 'driver-applications/placeholder/license.jpg',
            'vehicle_photo_path' => 'driver-applications/placeholder/vehicle.jpg',
            'status' => DriverApplicationStatus::Pending,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => DriverApplicationStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => DriverApplicationStatus::Rejected,
            'rejection_reason' => 'Documents incomplets.',
            'reviewed_at' => now(),
        ]);
    }
}
