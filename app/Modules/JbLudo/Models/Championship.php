<?php

namespace App\Modules\JbLudo\Models;

use App\Models\User;
use App\Modules\JbLudo\Enums\ChampionshipStatus;
use App\Modules\JbLudo\Enums\CompetitionScope;
use App\Modules\JbLudo\Enums\GameType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Championship extends Model
{
    protected $table = 'jb_championships';

    protected $fillable = [
        'created_by',
        'game_type',
        'scope',
        'country',
        'city',
        'neighborhood',
        'name',
        'description',
        'status',
        'max_participants',
        'prize_title',
        'prize_amount',
        'prize_currency',
        'prize_description',
        'participants_count',
        'rounds_count',
        'current_round',
        'registration_closes_at',
        'starts_at',
        'bracket_generated_at',
        'champion_player_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => ChampionshipStatus::class,
            'game_type' => GameType::class,
            'scope' => CompetitionScope::class,
            'prize_amount' => 'integer',
            'registration_closes_at' => 'datetime',
            'starts_at' => 'datetime',
            'bracket_generated_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function participants(): HasMany
    {
        return $this->hasMany(ChampionshipParticipant::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(GameMatch::class, 'championship_id');
    }

    public function champion(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class, 'champion_player_id');
    }
}
