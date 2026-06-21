<?php

namespace App\Modules\Municipality\Models;

use App\Models\User;
use App\Modules\Municipality\Enums\TreasuryRemittanceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MunicipalTreasuryRemittance extends Model
{
    protected $fillable = [
        'reference',
        'amount_xaf',
        'status',
        'prepared_by',
        'validated_by',
        'remitted_at',
        'period_start',
        'period_end',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount_xaf' => 'decimal:2',
            'status' => TreasuryRemittanceStatus::class,
            'remitted_at' => 'datetime',
            'period_start' => 'date',
            'period_end' => 'date',
        ];
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
