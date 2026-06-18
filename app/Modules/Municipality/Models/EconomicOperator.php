<?php

namespace App\Modules\Municipality\Models;

use App\Models\User;
use App\Modules\Core\Models\Attachment;
use App\Modules\Core\Models\Location;
use App\Modules\Municipality\Enums\SyncStatus;
use App\Modules\Municipality\Enums\TaxStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EconomicOperator extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'public_id',
        'territory_id',
        'sector_id',
        'operational_zone_id',
        'economic_zone_id',
        'arrondissement_sector_id',
        'category_id',
        'commercial_name',
        'activity_label',
        'responsible_name',
        'phone',
        'email',
        'latitude',
        'longitude',
        'gps_accuracy_m',
        'gps_captured_at',
        'sync_status',
        'registration_date',
        'registered_by',
        'last_modified_by',
        'last_visit_at',
        'secteur',
        'current_tax_status',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'gps_accuracy_m' => 'decimal:2',
            'gps_captured_at' => 'datetime',
            'sync_status' => SyncStatus::class,
            'registration_date' => 'date',
            'last_visit_at' => 'datetime',
            'current_tax_status' => TaxStatus::class,
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(MunicipalTerritory::class, 'territory_id');
    }

    public function sector(): BelongsTo
    {
        return $this->belongsTo(MunicipalSector::class, 'sector_id');
    }

    public function operationalZone(): BelongsTo
    {
        return $this->belongsTo(MunicipalSector::class, 'operational_zone_id');
    }

    public function economicZone(): BelongsTo
    {
        return $this->belongsTo(EconomicZone::class, 'economic_zone_id');
    }

    public function arrondissement(): BelongsTo
    {
        return $this->belongsTo(MunicipalSector::class, 'arrondissement_sector_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EconomicOperatorCategory::class, 'category_id');
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function lastModifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_modified_by');
    }

    public function taxStatuses(): HasMany
    {
        return $this->hasMany(EconomicOperatorTaxStatus::class);
    }

    public function qrcodes(): HasMany
    {
        return $this->hasMany(EconomicOperatorQrcode::class, 'operator_id');
    }

    public function activeQrcode(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EconomicOperatorQrcode::class, 'operator_id')
            ->where('is_active', true)
            ->latestOfMany('generated_at');
    }

    public function fieldVisits(): HasMany
    {
        return $this->hasMany(FieldVisit::class, 'operator_id');
    }

    public function municipalPayments(): HasMany
    {
        return $this->hasMany(MunicipalPayment::class, 'operator_id');
    }

    public function taxAssignments(): HasMany
    {
        return $this->hasMany(OperatorTaxAssignment::class, 'operator_id');
    }

    public function activeTaxAssignments(): HasMany
    {
        return $this->hasMany(OperatorTaxAssignment::class, 'operator_id')
            ->where('is_active', true);
    }

    public function fiscalObligations(): HasMany
    {
        return $this->hasMany(FiscalObligation::class, 'operator_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function locations(): MorphMany
    {
        return $this->morphMany(Location::class, 'locatable');
    }
}
