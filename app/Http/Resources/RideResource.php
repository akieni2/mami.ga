<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RideResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'client_id' => $this->client_id,
            'driver_id' => $this->driver_id,
            'pickup_label' => $this->pickup_label,
            'destination_label' => $this->destination_label,
            'pickup_source' => $this->pickup_source?->value ?? $this->pickup_source,
            'destination_source' => $this->destination_source?->value ?? $this->destination_source,
            'pickup_latitude' => $this->pickup_latitude !== null ? (float) $this->pickup_latitude : null,
            'pickup_longitude' => $this->pickup_longitude !== null ? (float) $this->pickup_longitude : null,
            'destination_latitude' => $this->destination_latitude !== null ? (float) $this->destination_latitude : null,
            'destination_longitude' => $this->destination_longitude !== null ? (float) $this->destination_longitude : null,
            'status' => $this->status?->value ?? $this->status,
            'estimated_price' => $this->estimated_price !== null ? (float) $this->estimated_price : null,
            'suggested_price' => $this->displaySuggestedPrice(),
            'proposed_price' => $this->proposed_price !== null ? (float) $this->proposed_price : null,
            'agreed_price' => $this->agreed_price !== null ? (float) $this->agreed_price : null,
            'payment_method' => $this->payment_method?->value ?? $this->payment_method,
            'distance_km' => $this->distance_km !== null ? (float) $this->distance_km : null,
            'duration_minutes' => $this->duration_minutes,
            'booking_type' => $this->booking_type?->value ?? $this->booking_type,
            'started_at' => $this->started_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'client' => $this->whenLoaded('client', fn () => [
                'id' => $this->client->id,
                'name' => $this->client->name,
                'phone' => $this->client->phone,
            ]),
            'driver' => $this->whenLoaded('driver', fn () => new DriverResource($this->driver)),
        ];
    }
}
