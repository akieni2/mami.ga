<?php

namespace App\Events;

use App\Events\Concerns\BroadcastsMamiRealtime;
use App\Models\Ride;
use App\Models\RideOffer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RideOfferAccepted implements ShouldBroadcastNow
{
    use BroadcastsMamiRealtime, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public RideOffer $offer,
        public Ride $ride,
    ) {}

    public function broadcastOn(): array
    {
        return $this->rideBroadcastChannels($this->ride);
    }

    public function broadcastWith(): array
    {
        return $this->firebaseEnvelope([
            'offer_id' => $this->offer->id,
            'ride_id' => $this->ride->id,
            'client_id' => $this->ride->client_id,
            'driver_id' => $this->ride->driver_id,
            'status' => $this->ride->status->value,
            'pickup_label' => $this->ride->pickup_label,
            'destination_label' => $this->ride->destination_label,
            'agreed_price' => $this->ride->agreed_price !== null ? (float) $this->ride->agreed_price : null,
            'proposed_price' => $this->ride->proposed_price !== null ? (float) $this->ride->proposed_price : null,
            'payment_method' => $this->ride->payment_method?->value,
        ]);
    }
}
