<?php

namespace App\Modules\Municipality\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MunicipalSector extends Model
{
    protected $fillable = [
        'territory_id',
        'name',
        'slug',
        'code',
        'sector_type',
        'parent_id',
        'center_latitude',
        'center_longitude',
        'polygon_geojson',
    ];

    protected function casts(): array
    {
        return [
            'center_latitude' => 'float',
            'center_longitude' => 'float',
            'polygon_geojson' => 'array',
        ];
    }

    public function territory(): BelongsTo
    {
        return $this->belongsTo(MunicipalTerritory::class, 'territory_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }
}
