<?php

namespace App\Modules\Municipality\Models;

use App\Models\User;
use App\Modules\Municipality\Enums\CashSessionClosureType;
use App\Modules\Municipality\Enums\CashSessionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashSession extends Model
{
    protected $fillable = [
        'reference',
        'agent_id',
        'financial_mission_id',
        'opened_at',
        'closed_at',
        'admin_closed_by',
        'closure_type',
        'opening_amount_xaf',
        'expected_amount_xaf',
        'actual_amount_xaf',
        'status',
        'opening_latitude',
        'opening_longitude',
        'closing_latitude',
        'closing_longitude',
        'device_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'opening_amount_xaf' => 'decimal:2',
            'expected_amount_xaf' => 'decimal:2',
            'actual_amount_xaf' => 'decimal:2',
            'status' => CashSessionStatus::class,
            'closure_type' => CashSessionClosureType::class,
            'opening_latitude' => 'decimal:7',
            'opening_longitude' => 'decimal:7',
            'closing_latitude' => 'decimal:7',
            'closing_longitude' => 'decimal:7',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function financialMission(): BelongsTo
    {
        return $this->belongsTo(FinancialMission::class, 'financial_mission_id');
    }

    public function adminClosedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_closed_by');
    }

    public function municipalPayments(): HasMany
    {
        return $this->hasMany(MunicipalPayment::class, 'cash_session_id');
    }

    public function isOpen(): bool
    {
        return $this->status === CashSessionStatus::Open;
    }
}
