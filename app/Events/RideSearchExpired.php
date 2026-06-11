<?php

namespace App\Events;

use App\Events\Concerns\BroadcastsMamiRealtime;
use App\Models\Ride;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RideSearchExpired implements ShouldBroadcastNow
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
            'client_id' => $this->ride->client_id,
            'status' => $this->ride->status->value,
            'reason' => 'search_timeout',
            'expired_at' => now()->toIso8601String(),
            'dispatch_expires_at' => $this->ride->dispatch_expires_at?->toIso8601String(),
        ]);
    }
}
