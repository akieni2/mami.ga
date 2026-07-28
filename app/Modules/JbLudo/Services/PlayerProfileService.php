<?php

namespace App\Modules\JbLudo\Services;

use App\Models\User;
use App\Modules\JbLudo\Enums\PlayerLevel;
use App\Modules\JbLudo\Models\PlayerProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlayerProfileService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function upsert(User $user, array $data): PlayerProfile
    {
        $phone = preg_replace('/\s+/', '', (string) $data['phone']);

        $duplicate = PlayerProfile::query()
            ->where('phone', $phone)
            ->where('user_id', '!=', $user->id)
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'phone' => ['Ce numéro de téléphone est déjà associé à un compte JB Ludo.'],
            ]);
        }

        return DB::transaction(function () use ($user, $data, $phone): PlayerProfile {
            $profile = PlayerProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'pseudo' => $data['pseudo'],
                    'photo_path' => $data['photo_path'] ?? null,
                    'city' => $data['city'] ?? null,
                    'country' => $data['country'] ?? 'Gabon',
                    'phone' => $phone,
                    'level' => PlayerLevel::from($data['level'] ?? PlayerLevel::Intermediate->value),
                    'club' => $data['club'] ?? null,
                    'is_online' => true,
                    'last_seen_at' => now(),
                ],
            );

            return $profile->fresh();
        });
    }

    public function forUser(User $user): PlayerProfile
    {
        $profile = PlayerProfile::query()->where('user_id', $user->id)->first();

        if ($profile === null) {
            throw ValidationException::withMessages([
                'profile' => ['Créez d\'abord votre profil joueur JB Ludo.'],
            ]);
        }

        if ($profile->is_suspended) {
            throw ValidationException::withMessages([
                'profile' => ['Votre compte JB Ludo est suspendu.'],
            ]);
        }

        $profile->update([
            'is_online' => true,
            'last_seen_at' => now(),
        ]);

        return $profile->fresh();
    }

    public function setOnline(PlayerProfile $profile, bool $online): void
    {
        $profile->update([
            'is_online' => $online,
            'last_seen_at' => now(),
        ]);
    }
}
