<?php

namespace Database\Factories;

use App\Models\Driver;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Vehicle>
 */
class VehicleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'driver_id' => Driver::factory(),
            'brand' => fake()->randomElement(['Toyota', 'Hyundai', 'Peugeot', 'Renault']),
            'model' => fake()->word(),
            'plate_number' => strtoupper(fake()->unique()->bothify('GA-###-??')),
            'color' => fake()->safeColorName(),
            'year' => fake()->numberBetween(2010, (int) date('Y')),
        ];
    }
}
