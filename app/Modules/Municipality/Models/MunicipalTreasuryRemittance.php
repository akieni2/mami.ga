<?php

namespace App\Modules\Municipality\Models;

use App\Models\User;
use App\Modules\Municipality\Enums\TreasuryRemittanceAccountingExportStatus;
use App\Modules\Municipality\Enums\TreasuryRemittanceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MunicipalTreasuryRemittance extends Model
{
    protected $fillable = [
        'reference',
        'amount_xaf',
        'reconciled_amount_xaf',
        'payment_count',
        'cash_session_count',
        'status',
        'prepared_by',
        'validated_by',
        'remitted_at',
        'period_start',
        'period_end',
        'notes',
        'slip_number',
        'bank_name',
        'deposit_reference',
        'deposited_at',
        'treasury_receipt_ref',
        'confirmed_at',
        'rejection_reason',
        'controlled_by',
        'controlled_at',
        'daf_validated_by',
        'daf_validated_at',
        'receveur_validated_by',
        'receveur_validated_at',
        'deposited_by',
        'confirmed_by',
        'accounting_batch_id',
        'accounting_export_status',
        'accounting_posted_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_xaf' => 'decimal:2',
            'reconciled_amount_xaf' => 'decimal:2',
            'status' => TreasuryRemittanceStatus::class,
            'accounting_export_status' => TreasuryRemittanceAccountingExportStatus::class,
            'remitted_at' => 'datetime',
            'period_start' => 'date',
            'period_end' => 'date',
            'deposited_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'controlled_at' => 'datetime',
            'daf_validated_at' => 'datetime',
            'receveur_validated_at' => 'datetime',
            'accounting_posted_at' => 'datetime',
        ];
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function controller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'controlled_by');
    }

    public function dafValidator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'daf_validated_by');
    }

    public function receveurValidator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receveur_validated_by');
    }

    public function depositor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deposited_by');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function paymentAllocations(): HasMany
    {
        return $this->hasMany(MunicipalTreasuryRemittancePayment::class, 'remittance_id');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(MunicipalTreasuryRemittanceApproval::class, 'remittance_id');
    }

    /**
     * @return list<int>
     */
    public function validationActorIds(): array
    {
        return array_values(array_filter([
            $this->prepared_by,
            $this->controlled_by,
            $this->daf_validated_by,
            $this->receveur_validated_by,
        ]));
    }
}
