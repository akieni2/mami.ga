<?php

namespace App\Modules\JbLudo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MatchmakingQueueEntry extends Model
{
    protected $table = 'jb_matchmaking_queue';

    protected $fillable = [
        'player_id',
        'game_type',
        'level',
        'points',
        'queued_at',
    ];

    protected function casts(): array
    {
        return [
            'queued_at' => 'datetime',
        ];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class, 'player_id');
    }
}
