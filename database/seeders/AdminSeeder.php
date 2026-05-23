<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@mami.ga'],
            [
                'name' => 'Administrateur MAMI.GA',
                'phone' => '+241060000999',
                'password' => Hash::make('password'),
                'is_admin' => true,
            ],
        );
    }
}
