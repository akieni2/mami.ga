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
            'pickup_latitude' => (float) $this->pickup_latitude,
            'pickup_longitude' => (float) $this->pickup_longitude,
            'destination_latitude' => (float) $this->destination_latitude,
            'destination_longitude' => (float) $this->destination_longitude,
            'status' => $this->status?->value ?? $this->status,
            'estimated_price' => $this->estimated_price !== null ? (float) $this->estimated_price : null,
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
