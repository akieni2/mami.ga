<?php

namespace App\Models;

use App\Enums\RideEventType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RideEvent extends Model
{
    protected $fillable = [
        'ride_id',
        'driver_id',
        'event_type',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => RideEventType::class,
            'payload' => 'array',
        ];
    }

    public function ride(): BelongsTo
    {
        return $this->belongsTo(Ride::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
