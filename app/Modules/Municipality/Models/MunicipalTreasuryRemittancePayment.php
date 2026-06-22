<?php

namespace App\Modules\Municipality\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MunicipalTreasuryRemittancePayment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'remittance_id',
        'municipal_payment_id',
        'cash_session_id',
        'amount_allocated',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_allocated' => 'decimal:2',
            'created_at' => 'datetime',
        ];
    }

    public function remittance(): BelongsTo
    {
        return $this->belongsTo(MunicipalTreasuryRemittance::class, 'remittance_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(MunicipalPayment::class, 'municipal_payment_id');
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class, 'cash_session_id');
    }
}
