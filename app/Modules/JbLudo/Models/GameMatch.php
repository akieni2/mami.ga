<?php

namespace App\Modules\JbLudo\Models;

use App\Modules\JbLudo\Enums\GameMode;
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
        'mode',
        'status',
        'white_player_id',
        'black_player_id',
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
            'status' => MatchStatus::class,
            'turn_color' => PieceColor::class,
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
