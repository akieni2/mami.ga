<?php

namespace App\Modules\JbLudo\Enums;

enum TransferRequestStatus: string
{
    case PendingCommission = 'pending_commission';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
