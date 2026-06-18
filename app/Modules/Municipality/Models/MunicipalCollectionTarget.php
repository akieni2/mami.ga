<?php

namespace App\Modules\Municipality\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MunicipalCollectionTarget extends Model
{
    protected $fillable = [
        'tax_type_id',
        'fiscal_year',
        'target_amount_xaf',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fiscal_year' => 'integer',
            'target_amount_xaf' => 'decimal:2',
        ];
    }

    public function taxType(): BelongsTo
    {
        return $this->belongsTo(MunicipalTaxType::class, 'tax_type_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
