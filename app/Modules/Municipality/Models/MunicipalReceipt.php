<?php

namespace App\Modules\Municipality\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MunicipalReceipt extends Model
{
    protected $fillable = [
        'payment_id',
        'receipt_number',
        'receipt_qr_value',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(MunicipalPayment::class, 'payment_id');
    }
}
