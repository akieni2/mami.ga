<?php

namespace App\Enums;

enum RideEventType: string
{
    case RideRequested = 'ride_requested';
    case RideAssigned = 'ride_assigned';
    case RideAccepted = 'ride_accepted';
    case DriverArrived = 'driver_arrived';
    case RideStarted = 'ride_started';
    case RideCompleted = 'ride_completed';
    case RideRejected = 'ride_rejected';
    case DriverLocationUpdated = 'driver_location_updated';
}
