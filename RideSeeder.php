<?php

namespace Database\Seeders;

use App\Enums\RideStatus;
use App\Models\Ride;
use App\Models\User;
use Illuminate\Database\Seeder;

class RideSeeder extends Seeder
{
    public function run(): void
    {
        $client = User::query()->where('email', 'client@mami.ga')->first();

        if ($client === null) {
            return;
        }

        Ride::factory()->count(3)->create([
            'client_id' => $client->id,
            'status' => RideStatus::Completed,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);
    }
}
