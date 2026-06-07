<?php

namespace App\Events;

use App\Events\Concerns\BroadcastsMamiRealtime;
use App\Models\Ride;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RideCompleted implements ShouldBroadcastNow
{
    use BroadcastsMamiRealtime, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Ride $ride) {}

    public function broadcastOn(): array
    {
        return $this->rideBroadcastChannels($this->ride);
    }

    public function broadcastWith(): array
    {
        return $this->firebaseEnvelope([
            'ride_id' => $this->ride->id,
            'status' => $this->ride->status->value,
            'completed_at' => $this->ride->completed_at?->toIso8601String(),
        ]);
    }
}
