<?php

namespace App\Services;

use App\Enums\RideEventType;
use App\Models\Ride;
use App\Models\RideEvent;

class RideEventRecorder
{
    public function record(Ride $ride, RideEventType $type, array $payload = []): RideEvent
    {
        return RideEvent::query()->create([
            'ride_id' => $ride->id,
            'driver_id' => $ride->driver_id,
            'event_type' => $type,
            'payload' => $payload,
        ]);
    }
}
