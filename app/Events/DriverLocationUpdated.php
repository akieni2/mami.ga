<?php

namespace App\Events;

use App\Events\Concerns\BroadcastsMamiRealtime;
use App\Models\Driver;
use App\Models\Ride;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverLocationUpdated implements ShouldBroadcastNow
{
    use BroadcastsMamiRealtime, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Driver $driver,
        public ?Ride $activeRide = null,
        public ?float $distanceToClientKm = null,
        public ?int $etaMinutes = null,
    ) {}

    public function broadcastOn(): array
    {
        return $this->driverBroadcastChannels($this->driver, $this->activeRide);
    }

    public function broadcastWith(): array
    {
        return $this->firebaseEnvelope([
            'driver_id' => $this->driver->id,
            'latitude' => $this->driver->latitude !== null ? (float) $this->driver->latitude : null,
            'longitude' => $this->driver->longitude !== null ? (float) $this->driver->longitude : null,
            'presence' => $this->driver->presenceStatus(),
            'last_seen_at' => $this->driver->last_seen_at?->toIso8601String(),
            'ride_id' => $this->activeRide?->id,
            'distance_to_client_km' => $this->distanceToClientKm,
            'eta_minutes' => $this->etaMinutes,
        ]);
    }
}
