<?php

namespace App\Modules\JbLudo\Models;

use App\Modules\JbLudo\Enums\ChampionshipParticipantStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChampionshipParticipant extends Model
{
    protected $table = 'jb_championship_participants';

    protected $fillable = [
        'championship_id',
        'player_profile_id',
        'seed_number',
        'status',
        'bye_count',
        'eliminated_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ChampionshipParticipantStatus::class,
            'eliminated_at' => 'datetime',
        ];
    }

    public function championship(): BelongsTo
    {
        return $this->belongsTo(Championship::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class, 'player_profile_id');
    }
}
