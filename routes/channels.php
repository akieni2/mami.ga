<?php

use App\Models\Ride;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Auth Sanctum pour applications mobiles (Reverb / Pusher protocol)
|--------------------------------------------------------------------------
*/
Broadcast::routes(['middleware' => ['auth:sanctum']]);

$prefix = config('mami.broadcast_prefix', 'mami');

/*
|--------------------------------------------------------------------------
| Canaux Reverb Sprint 02 (private-*)
|--------------------------------------------------------------------------
*/
Broadcast::channel('user-{userId}', function ($user, int $userId) {
    return (int) $user->id === $userId
        ? ['id' => $user->id, 'role' => 'client']
        : false;
});

Broadcast::channel('driver-{driverId}', function ($user, int $driverId) {
    if ($user->driver?->id === $driverId) {
        return ['id' => $user->id, 'role' => 'driver'];
    }

    $trackingRide = Ride::query()
        ->where('driver_id', $driverId)
        ->where('client_id', $user->id)
        ->whereNotIn('status', ['completed', 'cancelled'])
        ->exists();

    return $trackingRide
        ? ['id' => $user->id, 'role' => 'client']
        : false;
});

Broadcast::channel('ride-{rideId}', function ($user, int $rideId) {
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

/*
|--------------------------------------------------------------------------
| Canaux legacy (compatibilité admin / Phase 2)
|--------------------------------------------------------------------------
*/
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
