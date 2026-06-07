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

        return $query->get()
            ->map(fn (Driver $driver) => $this->formatDriver($driver))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function driverPayload(Driver $driver): array
    {
        $driver->loadMissing(['user', 'vehicle']);

        return $this->formatDriver($driver);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatDriver(Driver $driver): array
    {
        return [
            'id' => $driver->id,
            'name' => $driver->user?->name ?? 'Chauffeur #'.$driver->id,
            'email' => $driver->user?->email,
            'phone' => $driver->user?->phone,
            'license_number' => $driver->license_number,
            'presence' => $driver->presenceStatus(),
            'status' => $driver->status?->value,
            'is_available' => (bool) $driver->is_available,
            'latitude' => $driver->latitude !== null ? (float) $driver->latitude : null,
            'longitude' => $driver->longitude !== null ? (float) $driver->longitude : null,
            'rating' => $driver->rating !== null ? number_format((float) $driver->rating, 1) : null,
            'vehicle' => $driver->vehicle
                ? $driver->vehicle->brand.' '.$driver->vehicle->model.' ('.$driver->vehicle->plate_number.')'
                : null,
            'vehicle_brand' => $driver->vehicle?->brand,
            'vehicle_model' => $driver->vehicle?->model,
            'plate_number' => $driver->vehicle?->plate_number,
            'last_seen_at' => $driver->last_seen_at?->toIso8601String(),
        ];
    }
}
