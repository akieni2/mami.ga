<?php

namespace App\Modules\JbLudo\Models;

use App\Models\User;
use App\Modules\JbLudo\Enums\PlayerLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlayerProfile extends Model
{
    protected $table = 'jb_player_profiles';

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'pseudo',
        'photo_path',
        'city',
        'neighborhood',
        'country',
        'phone',
        'level',
        'club',
        'points',
        'games_played',
        'games_won',
        'games_drawn',
        'games_lost',
        'resign_count',
        'is_online',
        'is_suspended',
        'suspended_at',
        'suspension_reason',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'level' => PlayerLevel::class,
            'is_online' => 'boolean',
            'is_suspended' => 'boolean',
            'suspended_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ratingEvents(): HasMany
    {
        return $this->hasMany(RatingEvent::class, 'player_id');
    }
}
