<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RideDispatchWave extends Model
{
    protected $fillable = [
        'ride_id',
        'radius_min_km',
        'radius_max_km',
        'drivers_notified',
        'started_at',
        'ended_at',
    ];

    protected function casts(): array
    {
        return [
            'radius_min_km' => 'float',
            'radius_max_km' => 'float',
            'drivers_notified' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function ride(): BelongsTo
    {
        return $this->belongsTo(Ride::class);
    }
}
