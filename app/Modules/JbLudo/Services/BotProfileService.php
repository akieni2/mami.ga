<?php

namespace App\Modules\JbLudo\Services;

use App\Models\User;
use App\Modules\JbLudo\Enums\PlayerLevel;
use App\Modules\JbLudo\Models\PlayerProfile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class BotProfileService
{
    public function profile(string $code, string $pseudo): PlayerProfile
    {
        $email = 'bot+'.Str::slug($code).'@jb-games.local';
        $phone = 'BOT-'.Str::upper(Str::slug($code, '-'));

        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $pseudo,
                'password' => Hash::make(Str::password(32)),
                'is_admin' => false,
            ],
        );

        return PlayerProfile::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'first_name' => 'IA',
                'last_name' => $pseudo,
                'pseudo' => $pseudo,
                'phone' => $phone,
                'country' => 'Gabon',
                'city' => 'Virtuel',
                'neighborhood' => 'Entrainement',
                'level' => PlayerLevel::Intermediate,
                'club' => 'JB IA',
                'points' => 0,
                'is_online' => true,
            ],
        );
    }
}
