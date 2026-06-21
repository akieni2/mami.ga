<?php

namespace App\Modules\Municipality\Models;

use App\Models\User;
use App\Modules\Municipality\Enums\FinancialMissionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialMission extends Model
{
    protected $fillable = [
        'reference',
        'title',
        'agent_id',
        'operational_zone_id',
        'valid_from',
        'valid_until',
        'status',
        'created_by',
        'authorized_by',
        'authorized_at',
        'closed_by',
        'closed_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'valid_from' => 'date',
            'valid_until' => 'date',
            'status' => FinancialMissionStatus::class,
            'authorized_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function operationalZone(): BelongsTo
    {
        return $this->belongsTo(MunicipalSector::class, 'operational_zone_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function authorizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function cashSessions(): HasMany
    {
        return $this->hasMany(CashSession::class, 'financial_mission_id');
    }

    public function isActiveOn(string $date): bool
    {
        if ($this->status !== FinancialMissionStatus::Authorized) {
            return false;
        }

        return $date >= $this->valid_from->toDateString()
            && $date <= $this->valid_until->toDateString();
    }
}
