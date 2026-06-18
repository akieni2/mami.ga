<?php

namespace App\Modules\Municipality\Models;

use App\Models\User;
use App\Modules\Core\Models\Payment;
use App\Modules\Municipality\Enums\PaymentMethod;
use App\Modules\Municipality\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MunicipalPayment extends Model
{
    protected $fillable = [
        'operator_id',
        'agent_id',
        'cash_session_id',
        'core_payment_id',
        'amount',
        'payment_method',
        'payment_period',
        'status',
        'latitude',
        'longitude',
        'gps_accuracy_m',
        'device_id',
        'notes',
        'collected_at',
        'client_operation_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'gps_accuracy_m' => 'decimal:2',
            'collected_at' => 'datetime',
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

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class, 'cash_session_id');
    }

    public function corePayment(): BelongsTo
    {
        return $this->belongsTo(Payment::class, 'core_payment_id');
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(MunicipalPaymentAllocation::class, 'municipal_payment_id');
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(MunicipalReceipt::class, 'payment_id');
    }
}
