<?php

namespace App\Modules\Municipality\Models;

use App\Models\User;
use App\Modules\Municipality\Enums\PaymentMethod;
use App\Modules\Municipality\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MunicipalPayment extends Model
{
    protected $fillable = [
        'operator_id',
        'agent_id',
        'amount',
        'payment_method',
        'payment_period',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
        ];
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(EconomicOperator::class, 'operator_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(MunicipalReceipt::class, 'payment_id');
    }
}
