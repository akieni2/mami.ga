<?php

namespace Database\Seeders;

use App\Enums\DriverStatus;
use App\Models\Driver;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DriverSeeder extends Seeder
{
    public function run(): void
    {
        $drivers = [
            [
                'name' => 'Jean Moussavou',
                'email' => 'jean.driver@mami.ga',
                'phone' => '+241060000001',
                'license_number' => 'GA-2024-001',
                'latitude' => 0.4162,
                'longitude' => 9.4673,
            ],
            [
                'name' => 'Paul Obame',
                'email' => 'paul.driver@mami.ga',
                'phone' => '+241060000002',
                'license_number' => 'GA-2024-002',
                'latitude' => 0.4200,
                'longitude' => 9.4700,
            ],
            [
                'name' => 'Marc Nguema',
                'email' => 'marc.driver@mami.ga',
                'phone' => '+241060000003',
                'license_number' => 'GA-2024-003',
                'latitude' => 0.4100,
                'longitude' => 9.4600,
            ],
        ];

        foreach ($drivers as $data) {
            $user = User::query()->firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'],
                    'password' => Hash::make('password'),
                ],
            );

            Driver::query()->firstOrCreate(
                ['user_id' => $user->id],
                [
                    'license_number' => $data['license_number'],
                    'is_available' => true,
                    'status' => DriverStatus::Online,
                    'latitude' => $data['latitude'],
                    'longitude' => $data['longitude'],
                    'rating' => 5.0,
                ],
            );
        }

        User::query()->firstOrCreate(
            ['email' => 'client@mami.ga'],
            [
                'name' => 'Client Demo',
                'phone' => '+241060000100',
                'password' => Hash::make('password'),
            ],
        );
    }
}
