<?php

namespace App\Modules\JbLudo\Models;

use App\Models\User;
use App\Modules\JbLudo\Enums\TransferRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransferRequest extends Model
{
    protected $table = 'jb_transfer_requests';

    protected $fillable = [
        'player_profile_id',
        'from_team_id',
        'to_team_id',
        'requested_by',
        'reviewed_by',
        'status',
        'reason',
        'commission_notes',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => TransferRequestStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function player(): BelongsTo
    {
        return $this->belongsTo(PlayerProfile::class, 'player_profile_id');
    }

    public function fromTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'from_team_id');
    }

    public function toTeam(): BelongsTo
    {
        return $this->belongsTo(Team::class, 'to_team_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
