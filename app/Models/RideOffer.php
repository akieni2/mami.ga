<?php

namespace App\Models;

use App\Enums\RideOfferStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RideOffer extends Model
{
    protected $fillable = [
        'ride_id',
        'driver_id',
        'status',
        'offered_price',
        'counter_price',
        'distance_to_pickup_km',
        'dispatch_score',
        'radius_wave',
        'expires_at',
        'responded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => RideOfferStatus::class,
            'offered_price' => 'float',
            'counter_price' => 'float',
            'distance_to_pickup_km' => 'float',
            'dispatch_score' => 'float',
            'expires_at' => 'datetime',
            'responded_at' => 'datetime',
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

    public function isPending(): bool
    {
        return $this->status === RideOfferStatus::Pending
            && $this->expires_at->isFuture();
    }
}
