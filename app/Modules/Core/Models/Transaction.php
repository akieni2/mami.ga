<?php

namespace App\Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    protected $fillable = [
        'payment_id',
        'type',
        'amount',
        'currency',
        'status',
        'provider',
        'provider_reference',
        'payload',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
