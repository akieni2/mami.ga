<?php

namespace App\Modules\Municipality\Models;

use App\Modules\Municipality\Enums\EconomicZoneKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EconomicZone extends Model
{
    protected $fillable = [
        'territory_id',
        'code',
        'name',
        'slug',
        'zone_kind',
        'operational_zone_id',
        'primary_sector_id',
        'center_latitude',
        'center_longitude',
        'polygon_geojson',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'zone_kind' => EconomicZoneKind::class,
            'center_latitude' => 'decimal:7',
            'center_longitude' => 'decimal:7',
            'polygon_geojson' => 'array',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(MunicipalTerritory::class, 'territory_id');
    }

    public function operationalZone(): BelongsTo
    {
        return $this->belongsTo(MunicipalSector::class, 'operational_zone_id');
    }

    public function primarySector(): BelongsTo
    {
        return $this->belongsTo(MunicipalSector::class, 'primary_sector_id');
    }

    public function operators(): HasMany
    {
        return $this->hasMany(EconomicOperator::class, 'economic_zone_id');
    }
}
