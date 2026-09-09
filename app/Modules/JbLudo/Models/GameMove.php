<?php

namespace App\Modules\JbLudo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameMove extends Model
{
    public $timestamps = false;

    protected $table = 'jb_match_moves';

    protected $fillable = [
        'match_id',
        'server_seq',
        'player_id',
        'color',
        'path',
        'captures',
        'became_king',
        'board_after',
        'played_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'path' => 'array',
            'captures' => 'array',
            'became_king' => 'boolean',
            'board_after' => 'array',
            'played_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class, 'player_id');
    }
}
