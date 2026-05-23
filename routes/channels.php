<?php

use App\Models\Ride;
use Illuminate\Support\Facades\Broadcast;

$prefix = config('mami.broadcast_prefix', 'mami');

Broadcast::channel("{$prefix}.rides.{rideId}", function ($user, int $rideId) {
    $ride = Ride::query()->find($rideId);

    if ($ride === null) {
        return false;
    }

    if ($ride->client_id === $user->id) {
        return ['id' => $user->id, 'role' => 'client'];
    }

    if ($user->driver?->id === $ride->driver_id) {
        return ['id' => $user->id, 'role' => 'driver'];
    }

    return false;
});

Broadcast::channel("{$prefix}.drivers.{driverId}", function ($user, int $driverId) {
    if ($user->driver?->id === $driverId) {
        return ['id' => $user->id, 'role' => 'driver'];
    }

    $trackingRide = Ride::query()
        ->where('driver_id', $driverId)
        ->where('client_id', $user->id)
        ->whereNotIn('status', ['completed', 'cancelled'])
        ->exists();

    if ($trackingRide) {
        return ['id' => $user->id, 'role' => 'client'];
    }

    return false;
});
