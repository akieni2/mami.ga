<?php

namespace App\Modules\Municipality\Models;

use App\Models\User;
use App\Modules\Municipality\Enums\TaxStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EconomicOperatorTaxStatus extends Model
{
    protected $table = 'economic_operator_tax_status';

    protected $fillable = [
        'economic_operator_id',
        'status',
        'effective_from',
        'effective_to',
        'days_overdue',
        'outstanding_amount',
        'assessed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => TaxStatus::class,
            'effective_from' => 'date',
            'effective_to' => 'date',
            'outstanding_amount' => 'decimal:2',
        ];
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(EconomicOperator::class, 'economic_operator_id');
    }

    public function assessedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assessed_by');
    }
}
