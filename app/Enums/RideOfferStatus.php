<?php

namespace App\Enums;

enum RideOfferStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Expired = 'expired';
    case Countered = 'countered';
}
