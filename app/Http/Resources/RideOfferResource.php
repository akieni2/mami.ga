<?php

namespace App\Http\Resources;

use App\Models\RideOffer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RideOffer */
class RideOfferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ride_id' => $this->ride_id,
            'driver_id' => $this->driver_id,
            'status' => $this->status->value,
            'offered_price' => (float) $this->offered_price,
            'counter_price' => $this->counter_price !== null ? (float) $this->counter_price : null,
            'distance_to_pickup_km' => (float) $this->distance_to_pickup_km,
            'dispatch_score' => $this->dispatch_score !== null ? (float) $this->dispatch_score : null,
            'radius_wave' => $this->radius_wave,
            'expires_at' => $this->expires_at->toIso8601String(),
            'responded_at' => $this->responded_at?->toIso8601String(),
            'ride' => $this->whenLoaded('ride', fn () => (new RideResource($this->ride))->resolve()),
        ];
    }
}
