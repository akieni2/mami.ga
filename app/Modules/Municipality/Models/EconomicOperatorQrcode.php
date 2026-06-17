<?php

namespace App\Modules\Municipality\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EconomicOperatorQrcode extends Model
{
    protected $fillable = [
        'operator_id',
        'qr_uuid',
        'qr_value',
        'generated_at',
        'printed_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'printed_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(EconomicOperator::class, 'operator_id');
    }
}
