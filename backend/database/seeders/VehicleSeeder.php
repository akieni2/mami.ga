<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        $vehicles = [
            ['plate' => 'GA-101-AA', 'brand' => 'Toyota', 'model' => 'Corolla', 'color' => 'white', 'year' => 2018],
            ['plate' => 'GA-102-BB', 'brand' => 'Hyundai', 'model' => 'Accent', 'color' => 'yellow', 'year' => 2019],
            ['plate' => 'GA-103-CC', 'brand' => 'Peugeot', 'model' => '301', 'color' => 'blue', 'year' => 2020],
        ];

        Driver::query()->orderBy('id')->get()->each(function (Driver $driver, int $index) use ($vehicles): void {
            $vehicle = $vehicles[$index] ?? $vehicles[0];

            Vehicle::query()->firstOrCreate(
                ['driver_id' => $driver->id],
                [
                    'brand' => $vehicle['brand'],
                    'model' => $vehicle['model'],
                    'plate_number' => $vehicle['plate'],
                    'color' => $vehicle['color'],
                    'year' => $vehicle['year'],
                ],
            );
        });
    }
}
