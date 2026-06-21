<?php

namespace App\Modules\Municipality\Models;

use App\Models\User;
use App\Modules\Municipality\Enums\FinancialMissionStatus;
use App\Modules\Municipality\Enums\FinancialMissionWorkflowStatus;
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
        'workflow_status',
        'submitted_at',
        'controller_reviewed_at',
        'daf_reviewed_at',
        'approved_at',
        'rejected_at',
        'controller_id',
        'daf_id',
        'rejection_reason',
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
            'workflow_status' => FinancialMissionWorkflowStatus::class,
            'submitted_at' => 'datetime',
            'controller_reviewed_at' => 'datetime',
            'daf_reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
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

    public function controller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'controller_id');
    }

    public function daf(): BelongsTo
    {
        return $this->belongsTo(User::class, 'daf_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(FinancialMissionApproval::class);
    }

    public function cashSessions(): HasMany
    {
        return $this->hasMany(CashSession::class, 'financial_mission_id');
    }

    public function isActiveOn(string $date): bool
    {
        if (! $this->isApprovedForCollection()) {
            return false;
        }

        return $date >= $this->valid_from->toDateString()
            && $date <= $this->valid_until->toDateString();
    }

    public function isApprovedForCollection(): bool
    {
        if ($this->workflow_status === FinancialMissionWorkflowStatus::Approved) {
            return true;
        }

        if (config('mami.municipality_finance.legacy_mission_authorize', true)) {
            return $this->status === FinancialMissionStatus::Authorized;
        }

        return false;
    }
}
