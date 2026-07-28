<?php

namespace App\Modules\JbLudo\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Modules\JbLudo\Models\PlayerProfile */
class PlayerProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'pseudo' => $this->pseudo,
            'photo_path' => $this->photo_path,
            'city' => $this->city,
            'country' => $this->country,
            'phone' => $this->phone,
            'level' => $this->level->value,
            'level_label' => $this->level->label(),
            'club' => $this->club,
            'points' => $this->points,
            'games_played' => $this->games_played,
            'games_won' => $this->games_won,
            'games_drawn' => $this->games_drawn,
            'games_lost' => $this->games_lost,
            'is_online' => $this->is_online,
            'is_suspended' => $this->is_suspended,
        ];
    }
}
