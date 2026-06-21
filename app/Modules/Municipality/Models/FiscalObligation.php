<?php

namespace App\Modules\Municipality\Models;

use App\Modules\Municipality\Enums\FiscalObligationStatus;
use App\Modules\Municipality\Enums\FiscalObligationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiscalObligation extends Model
{
    protected $fillable = [
        'operator_id',
        'tax_type_id',
        'tax_rate_id',
        'obligation_type',
        'reference',
        'period_start',
        'period_end',
        'amount_due',
        'amount_paid',
        'balance_due',
        'status',
        'generated_at',
        'due_date',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'amount_due' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'obligation_type' => FiscalObligationType::class,
            'status' => FiscalObligationStatus::class,
            'generated_at' => 'datetime',
            'due_date' => 'date',
        ];
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(EconomicOperator::class, 'operator_id');
    }

    public function taxType(): BelongsTo
    {
        return $this->belongsTo(MunicipalTaxType::class, 'tax_type_id');
    }

    public function taxRate(): BelongsTo
    {
        return $this->belongsTo(MunicipalTaxRate::class, 'tax_rate_id');
    }

    public function recalculateBalance(): void
    {
        $balance = (float) $this->amount_due - (float) $this->amount_paid;
        $this->balance_due = max(0, round($balance, 2));

        if ($this->balance_due <= 0 && (float) $this->amount_paid > 0) {
            $this->status = FiscalObligationStatus::Paid;
        } elseif ((float) $this->amount_paid > 0) {
            $this->status = FiscalObligationStatus::Partial;
        }
    }
}
