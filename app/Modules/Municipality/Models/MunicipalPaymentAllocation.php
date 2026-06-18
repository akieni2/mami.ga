<?php

namespace App\Modules\Municipality\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MunicipalPaymentAllocation extends Model
{
    protected $fillable = [
        'municipal_payment_id',
        'fiscal_obligation_id',
        'amount_allocated',
    ];

    protected function casts(): array
    {
        return [
            'amount_allocated' => 'decimal:2',
        ];
    }

    public function municipalPayment(): BelongsTo
    {
        return $this->belongsTo(MunicipalPayment::class, 'municipal_payment_id');
    }

    public function fiscalObligation(): BelongsTo
    {
        return $this->belongsTo(FiscalObligation::class, 'fiscal_obligation_id');
    }
}
