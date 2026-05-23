<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DriverSeeder::class,
            VehicleSeeder::class,
            AdminSeeder::class,
        ]);
    }
}
