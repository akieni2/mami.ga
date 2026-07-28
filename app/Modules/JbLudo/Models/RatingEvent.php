<?php

namespace App\Modules\JbLudo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RatingEvent extends Model
{
    public $timestamps = false;

    protected $table = 'jb_rating_events';

    protected $fillable = [
        'player_id',
        'match_id',
        'event_type',
        'delta',
        'points_after',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class, 'player_id');
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }
}
