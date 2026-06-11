<?php

namespace App\Models;

use App\Enums\BookingType;
use App\Enums\LocationSource;
use App\Enums\PaymentMethod;
use App\Enums\RideStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ride extends Model
{
    /** @use HasFactory<\Database\Factories\RideFactory> */
    use HasFactory;

    protected $fillable = [
        'client_id',
        'driver_id',
        'pickup_label',
        'destination_label',
        'pickup_source',
        'destination_source',
        'pickup_latitude',
        'pickup_longitude',
        'destination_latitude',
        'destination_longitude',
        'status',
        'booking_type',
        'scheduled_at',
        'activated_at',
        'estimated_price',
        'suggested_price',
        'proposed_price',
        'agreed_price',
        'payment_method',
        'balance_payment_method',
        'deposit_amount',
        'deposit_status',
        'distance_km',
        'duration_minutes',
        'search_radius_km',
        'dispatch_started_at',
        'dispatch_expires_at',
        'accepted_at',
        'cancelled_at',
        'cancelled_by_role',
        'cancellation_reason',
        'no_show_detected_at',
        'no_show_reported_by',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => RideStatus::class,
            'booking_type' => BookingType::class,
            'pickup_source' => LocationSource::class,
            'destination_source' => LocationSource::class,
            'payment_method' => PaymentMethod::class,
            'balance_payment_method' => PaymentMethod::class,
            'pickup_latitude' => 'float',
            'pickup_longitude' => 'float',
            'destination_latitude' => 'float',
            'destination_longitude' => 'float',
            'estimated_price' => 'float',
            'suggested_price' => 'float',
            'proposed_price' => 'float',
            'agreed_price' => 'float',
            'deposit_amount' => 'float',
            'distance_km' => 'float',
            'duration_minutes' => 'integer',
            'search_radius_km' => 'float',
            'scheduled_at' => 'datetime',
            'activated_at' => 'datetime',
            'dispatch_started_at' => 'datetime',
            'dispatch_expires_at' => 'datetime',
            'accepted_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'no_show_detected_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Prix conseillé affiché (V2) — fallback sur estimated_price V1.
     */
    public function displaySuggestedPrice(): ?float
    {
        return $this->suggested_price ?? $this->estimated_price;
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(RideEvent::class);
    }

    public function isTrackable(): bool
    {
        return ! in_array($this->status, [RideStatus::Completed, RideStatus::Cancelled], true);
    }

    public function hasPickupCoordinates(): bool
    {
        return $this->pickup_latitude !== null && $this->pickup_longitude !== null;
    }

    public function hasDestinationCoordinates(): bool
    {
        return $this->destination_latitude !== null && $this->destination_longitude !== null;
    }
}
