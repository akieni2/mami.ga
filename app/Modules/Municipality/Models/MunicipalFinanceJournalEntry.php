<?php

namespace App\Modules\Municipality\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MunicipalFinanceJournalEntry extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'event_type',
        'subject_type',
        'subject_id',
        'financial_mission_id',
        'cash_session_id',
        'actor_id',
        'payload',
        'occurred_at',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'occurred_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(FinancialMission::class, 'financial_mission_id');
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class, 'cash_session_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
