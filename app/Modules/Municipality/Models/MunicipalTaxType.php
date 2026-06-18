<?php

namespace App\Modules\Municipality\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MunicipalTaxType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function rates(): HasMany
    {
        return $this->hasMany(MunicipalTaxRate::class, 'tax_type_id');
    }

    public function activeRate(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(MunicipalTaxRate::class, 'tax_type_id')
            ->where('is_active', true)
            ->where('valid_from', '<=', now()->toDateString())
            ->where(function ($query): void {
                $query->whereNull('valid_to')
                    ->orWhere('valid_to', '>=', now()->toDateString());
            })
            ->latestOfMany('valid_from');
    }

    public function collectionTargets(): HasMany
    {
        return $this->hasMany(MunicipalCollectionTarget::class, 'tax_type_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(OperatorTaxAssignment::class, 'tax_type_id');
    }

    public function obligations(): HasMany
    {
        return $this->hasMany(FiscalObligation::class, 'tax_type_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
