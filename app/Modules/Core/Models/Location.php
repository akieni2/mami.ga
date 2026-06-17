<?php

namespace App\Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Location extends Model
{
    protected $fillable = [
        'locatable_type',
        'locatable_id',
        'latitude',
        'longitude',
        'accuracy_meters',
        'heading',
        'speed_kmh',
        'recorded_at',
        'context',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'speed_kmh' => 'float',
            'recorded_at' => 'datetime',
        ];
    }

    public function locatable(): MorphTo
    {
        return $this->morphTo();
    }
}
