<?php

namespace App\Events;

use App\Events\Concerns\BroadcastsMamiRealtime;
use App\Models\Ride;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RideRequested implements ShouldBroadcast
{
    use BroadcastsMamiRealtime, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Ride $ride) {}

    public function broadcastOn(): array
    {
        return [
            new Channel($this->channelName('rides.'.$this->ride->id)),
            new Channel($this->channelName('drivers.'.$this->ride->driver_id)),
        ];
    }

    public function broadcastWith(): array
    {
        return $this->firebaseEnvelope([
            'ride_id' => $this->ride->id,
            'client_id' => $this->ride->client_id,
            'driver_id' => $this->ride->driver_id,
            'status' => $this->ride->status->value,
            'pickup_latitude' => (float) $this->ride->pickup_latitude,
            'pickup_longitude' => (float) $this->ride->pickup_longitude,
            'destination_latitude' => (float) $this->ride->destination_latitude,
            'destination_longitude' => (float) $this->ride->destination_longitude,
            'estimated_price' => $this->ride->estimated_price !== null ? (float) $this->ride->estimated_price : null,
        ]);
    }
}
