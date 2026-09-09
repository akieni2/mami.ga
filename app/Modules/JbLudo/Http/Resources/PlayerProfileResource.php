<?php

namespace App\Modules\JbLudo\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'pseudo' => $this->pseudo,
            'photo_path' => $this->photo_path,
            'photo_url' => $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null,
            'city' => $this->city,
            'neighborhood' => $this->neighborhood,
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
