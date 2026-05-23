<?php

namespace App\Enums;

enum RideEventType: string
{
    case RideRequested = 'ride_requested';
    case RideAccepted = 'ride_accepted';
    case DriverArrived = 'driver_arrived';
    case RideStarted = 'ride_started';
    case RideCompleted = 'ride_completed';
    case DriverLocationUpdated = 'driver_location_updated';
}
