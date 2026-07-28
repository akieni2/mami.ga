<?php

namespace App\Modules\JbLudo\Models;

use App\Modules\JbLudo\Enums\InviteStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FriendInvite extends Model
{
    protected $table = 'jb_friend_invites';

    protected $fillable = [
        'from_player_id',
        'to_player_id',
        'match_id',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => InviteStatus::class,
        ];
    }

    public function fromPlayer(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class, 'from_player_id');
    }

    public function toPlayer(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class, 'to_player_id');
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(GameMatch::class, 'match_id');
    }
}
