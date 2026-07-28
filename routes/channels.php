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

Broadcast::channel('jb-match-{matchId}', function ($user, int $matchId) {
    $match = \App\Modules\JbLudo\Models\GameMatch::query()->find($matchId);
    if ($match === null) {
        return false;
    }

    $profile = \App\Modules\JbLudo\Models\PlayerProfile::query()
        ->where('user_id', $user->id)
        ->first();

    if ($profile === null) {
        return $user->isAdmin() ? ['id' => $user->id, 'role' => 'admin'] : false;
    }

    if (in_array($profile->id, [(int) $match->white_player_id, (int) $match->black_player_id], true)) {
        return ['id' => $user->id, 'role' => 'player', 'player_id' => $profile->id];
    }

    return $user->isAdmin() ? ['id' => $user->id, 'role' => 'admin'] : false;
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
