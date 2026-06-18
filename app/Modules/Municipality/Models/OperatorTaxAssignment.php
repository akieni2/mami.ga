<?php

namespace App\Modules\Municipality\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperatorTaxAssignment extends Model
{
    protected $fillable = [
        'operator_id',
        'tax_type_id',
        'assigned_at',
        'assigned_by',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'is_active' => 'boolean',
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

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
