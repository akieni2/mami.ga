<?php

namespace App\Modules\Municipality\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MunicipalTerritory extends Model
{
    protected $fillable = [
        'name',
        'code',
        'bounds_sw_lat',
        'bounds_sw_lng',
        'bounds_ne_lat',
        'bounds_ne_lng',
    ];

    public function sectors(): HasMany
    {
        return $this->hasMany(MunicipalSector::class, 'territory_id');
    }
}
