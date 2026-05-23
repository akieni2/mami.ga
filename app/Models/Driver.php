<?php

namespace App\Models;

use App\Enums\DriverStatus;
use App\Enums\RideStatus;
use App\Services\DriverPresenceService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Driver extends Model
{
    /** @use HasFactory<\Database\Factories\DriverFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'license_number',
        'is_available',
        'status',
        'latitude',
        'longitude',
        'last_seen_at',
        'rating',
    ];

    protected function casts(): array
    {
        return [
            'is_available' => 'boolean',
            'status' => DriverStatus::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'last_seen_at' => 'datetime',
            'rating' => 'float',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function vehicle(): HasOne
    {
        return $this->hasOne(Vehicle::class);
    }

    public function rides(): HasMany
    {
        return $this->hasMany(Ride::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(DriverLocation::class);
    }

    public function activeRide(): ?Ride
    {
        return $this->rides()
            ->whereIn('status', [
                RideStatus::Pending,
                RideStatus::Accepted,
                RideStatus::Arrived,
                RideStatus::Started,
            ])
            ->latest('id')
            ->first();
    }

    public function presenceStatus(): string
    {
        return app(DriverPresenceService::class)->resolvePresence($this);
    }

    public function hasGpsPosition(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }
}
