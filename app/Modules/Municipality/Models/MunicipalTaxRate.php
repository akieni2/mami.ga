<?php

namespace App\Modules\Municipality\Models;

use App\Models\User;
use App\Modules\Municipality\Enums\BillingPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MunicipalTaxRate extends Model
{
    protected $fillable = [
        'tax_type_id',
        'amount_xaf',
        'billing_period',
        'valid_from',
        'valid_to',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount_xaf' => 'decimal:2',
            'billing_period' => BillingPeriod::class,
            'valid_from' => 'date',
            'valid_to' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function taxType(): BelongsTo
    {
        return $this->belongsTo(MunicipalTaxType::class, 'tax_type_id');
    }

    public function obligations(): HasMany
    {
        return $this->hasMany(FiscalObligation::class, 'tax_rate_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isValidOn(\DateTimeInterface|string $date): bool
    {
        $check = $date instanceof \DateTimeInterface
            ? $date->format('Y-m-d')
            : $date;

        if (! $this->is_active) {
            return false;
        }

        if ($this->valid_from->format('Y-m-d') > $check) {
            return false;
        }

        if ($this->valid_to !== null && $this->valid_to->format('Y-m-d') < $check) {
            return false;
        }

        return true;
    }
}
