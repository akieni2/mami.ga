<?php

namespace App\Modules\JbLudo\Models;

use App\Modules\JbLudo\Enums\GameMode;
use App\Modules\JbLudo\Enums\GameType;
use App\Modules\JbLudo\Enums\MatchResult;
use App\Modules\JbLudo\Enums\MatchStatus;
use App\Modules\JbLudo\Enums\PieceColor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameMatch extends Model
{
    protected $table = 'jb_matches';

    protected $fillable = [
        'reference',
        'game_type',
        'mode',
        'championship_id',
        'championship_round',
        'championship_match_number',
        'status',
        'white_player_id',
        'black_player_id',
        'ludo_player_ids',
        'turn_color',
        'board_state',
        'clock_seconds',
        'white_time_left',
        'black_time_left',
        'turn_started_at',
        'grace_until',
        'disconnected_player_id',
        'winner_id',
        'result',
        'result_reason',
        'move_count',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'mode' => GameMode::class,
            'game_type' => GameType::class,
            'status' => MatchStatus::class,
            'turn_color' => PieceColor::class,
            'ludo_player_ids' => 'array',
            'result' => MatchResult::class,
            'board_state' => 'array',
            'turn_started_at' => 'datetime',
            'grace_until' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function whitePlayer(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class, 'white_player_id');
    }

    public function blackPlayer(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class, 'black_player_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class, 'winner_id');
    }

    public function moves(): HasMany
    {
        return $this->hasMany(GameMove::class, 'match_id')->orderBy('server_seq');
    }


    public function championship(): BelongsTo
    {
        return $this->belongsTo(Championship::class, 'championship_id');
    }
    public function playerColor(PlayerProfile $player): ?PieceColor
    {
        if ((int) $this->white_player_id === (int) $player->id) {
            return PieceColor::White;
        }

        if ((int) $this->black_player_id === (int) $player->id) {
            return PieceColor::Black;
        }

        return null;
    }

    public function ludoColor(PlayerProfile $player): ?string
    {
        $colors = $this->ludoColors($player);

        return $colors[0] ?? null;
    }

    /**
     * @return list<string>
     */
    public function ludoColors(PlayerProfile $player): array
    {
        $players = $this->ludo_player_ids ?? [];
        $colors = [];

        foreach ($players as $color => $playerId) {
            if ((int) $playerId === (int) $player->id) {
                $colors[] = (string) $color;
            }
        }

        return $colors;
    }

    public function opponentOf(PlayerProfile $player): ?PlayerProfile
    {
        if ((int) $this->white_player_id === (int) $player->id) {
            return $this->blackPlayer;
        }

        if ((int) $this->black_player_id === (int) $player->id) {
            return $this->whitePlayer;
        }

        return null;
    }
}
