<?php

namespace App\Events;

use App\Events\Concerns\BroadcastsMamiRealtime;
use App\Models\RideOffer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RideOfferCreated implements ShouldBroadcastNow
{
    use BroadcastsMamiRealtime, Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public RideOffer $offer) {}

    public function broadcastOn(): array
    {
        return $this->driverBroadcastChannels($this->offer->driver, $this->offer->ride);
    }

    public function broadcastWith(): array
    {
        $ride = $this->offer->ride;

        return $this->firebaseEnvelope([
            'offer_id' => $this->offer->id,
            'ride_id' => $ride->id,
            'driver_id' => $this->offer->driver_id,
            'pickup_label' => $ride->pickup_label,
            'destination_label' => $ride->destination_label,
            'proposed_price' => $ride->proposed_price !== null ? (float) $ride->proposed_price : null,
            'payment_method' => $ride->payment_method?->value,
            'pickup_latitude' => $ride->pickup_latitude,
            'pickup_longitude' => $ride->pickup_longitude,
            'destination_latitude' => $ride->destination_latitude,
            'destination_longitude' => $ride->destination_longitude,
            'distance_to_pickup_km' => (float) $this->offer->distance_to_pickup_km,
            'dispatch_score' => $this->offer->dispatch_score,
            'radius_wave' => $this->offer->radius_wave,
            'expires_at' => $this->offer->expires_at->toIso8601String(),
            'status' => $this->offer->status->value,
        ]);
    }
}
