<?php

namespace App\Modules\Municipality\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialMissionApproval extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'financial_mission_id',
        'action',
        'performed_by',
        'comments',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(FinancialMission::class, 'financial_mission_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
