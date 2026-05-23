<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DriverResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'license_number' => $this->license_number,
            'is_available' => $this->is_available,
            'status' => $this->status?->value ?? $this->status,
            'presence' => $this->presenceStatus(),
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'last_seen_at' => $this->last_seen_at?->toIso8601String(),
            'rating' => $this->rating !== null ? (float) $this->rating : null,
            'distance_km' => $this->when(isset($this->distance_km), fn () => round((float) $this->distance_km, 3)),
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'phone' => $this->user->phone,
            ]),
            'vehicle' => $this->whenLoaded('vehicle', fn () => [
                'id' => $this->vehicle->id,
                'brand' => $this->vehicle->brand,
                'model' => $this->vehicle->model,
                'plate_number' => $this->vehicle->plate_number,
                'color' => $this->vehicle->color,
                'year' => $this->vehicle->year,
            ]),
        ];
    }
}
