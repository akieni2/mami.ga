<?php

namespace App\Modules\JbLudo\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Modules\JbLudo\Enums\GameType;
use App\Modules\JbLudo\Models\PlayerProfile;

/** @mixin \App\Modules\JbLudo\Models\GameMatch */
class GameMatchResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profile = $request->user() === null
            ? null
            : PlayerProfile::query()->where('user_id', $request->user()->id)->first();

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'game_type' => $this->game_type->value,
            'mode' => $this->mode->value,
            'my_color' => $profile === null ? null : (
                $this->game_type === GameType::Ludo
                    ? $this->ludoColor($profile)
                    : $this->playerColor($profile)?->value
            ),
            'my_colors' => $profile === null || $this->game_type !== GameType::Ludo
                ? []
                : $this->ludoColors($profile),
            'status' => $this->status->value,
            'turn_color' => $this->turn_color->value,
            'board_state' => $this->board_state,
            'clock_seconds' => $this->clock_seconds,
            'white_time_left' => $this->white_time_left,
            'black_time_left' => $this->black_time_left,
            'ludo_player_ids' => $this->ludo_player_ids,
            'grace_until' => $this->grace_until?->toIso8601String(),
            'move_count' => $this->move_count,
            'result' => $this->result?->value,
            'result_reason' => $this->result_reason,
            'winner_id' => $this->winner_id,
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'white_player' => $this->whenLoaded('whitePlayer', fn () => new PlayerProfileResource($this->whitePlayer)),
            'black_player' => $this->whenLoaded('blackPlayer', fn () => new PlayerProfileResource($this->blackPlayer)),
            'moves' => $this->whenLoaded('moves', fn () => $this->moves->map(fn ($m) => [
                'server_seq' => $m->server_seq,
                'player_id' => $m->player_id,
                'color' => $m->color,
                'path' => $m->path,
                'captures' => $m->captures,
                'became_king' => $m->became_king,
                'played_at' => $m->played_at?->toIso8601String(),
            ])->values()->all()),
        ];
    }
}
