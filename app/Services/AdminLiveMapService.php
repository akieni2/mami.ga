<?php

namespace App\Services;

use App\Models\Driver;

class AdminLiveMapService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function driversPayload(bool $mapOnly = false): array
    {
        $query = Driver::query()->with(['user', 'vehicle'])->latest('id');

        if ($mapOnly) {
            $query->whereNotNull('latitude')->whereNotNull('longitude');
        }

        return $query->get()->map(fn (Driver $driver) => [
            'id' => $driver->id,
            'name' => $driver->user?->name ?? 'Chauffeur #'.$driver->id,
            'phone' => $driver->user?->phone,
            'license_number' => $driver->license_number,
            'presence' => $driver->presenceStatus(),
            'status' => $driver->status?->value,
            'latitude' => $driver->latitude !== null ? (float) $driver->latitude : null,
            'longitude' => $driver->longitude !== null ? (float) $driver->longitude : null,
            'rating' => $driver->rating !== null ? number_format((float) $driver->rating, 1) : null,
            'vehicle' => $driver->vehicle
                ? $driver->vehicle->brand.' '.$driver->vehicle->model.' ('.$driver->vehicle->plate_number.')'
                : null,
            'last_seen_at' => $driver->last_seen_at?->toIso8601String(),
        ])->values()->all();
    }
}
