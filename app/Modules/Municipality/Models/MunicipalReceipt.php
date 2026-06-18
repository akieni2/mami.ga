<?php

namespace App\Modules\Municipality\Models;

use App\Models\User;
use App\Modules\Municipality\Enums\ReceiptStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MunicipalReceipt extends Model
{
    protected $fillable = [
        'payment_id',
        'receipt_number',
        'receipt_qr_value',
        'verification_token',
        'document_hash',
        'signed_at',
        'status',
        'annulled_at',
        'annulled_by',
        'annulled_reason',
        'refunded_at',
        'refunded_by',
        'reprint_count',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'signed_at' => 'datetime',
            'annulled_at' => 'datetime',
            'refunded_at' => 'datetime',
            'status' => ReceiptStatus::class,
        ];
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(MunicipalPayment::class, 'payment_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(MunicipalReceiptDocument::class, 'municipal_receipt_id');
    }

    public function annulledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'annulled_by');
    }

    public function isValid(): bool
    {
        return $this->status === ReceiptStatus::Valid;
    }
}
