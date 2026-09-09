<?php

namespace App\Modules\JbLudo\Models;

use App\Modules\JbLudo\Enums\TeamMemberRole;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model
{
    protected $table = 'jb_team_members';

    protected $fillable = [
        'team_id',
        'player_profile_id',
        'role',
        'joined_at',
        'left_at',
    ];

    protected function casts(): array
    {
        return [
            'role' => TeamMemberRole::class,
            'joined_at' => 'datetime',
            'left_at' => 'datetime',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class, 'player_profile_id');
    }
}
