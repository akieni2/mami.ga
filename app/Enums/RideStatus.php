<?php

namespace App\Enums;

enum RideStatus: string
{
    case Searching = 'searching';
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Arrived = 'arrived';
    case Started = 'started';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}
