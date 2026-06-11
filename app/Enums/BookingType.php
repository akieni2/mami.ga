<?php

namespace App\Enums;

enum BookingType: string
{
    case Immediate = 'immediate';
    case Scheduled = 'scheduled';
}
