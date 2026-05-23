<?php

namespace App\Enums;

enum DriverStatus: string
{
    case Offline = 'offline';
    case Online = 'online';
    case OnRide = 'on_ride';
}
