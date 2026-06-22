<?php

namespace App\Modules\Municipality\Enums;

enum TreasuryRemittanceApprovalAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Controlled = 'controlled';
    case DafValidated = 'daf_validated';
    case ReceveurValidated = 'receveur_validated';
    case Deposited = 'deposited';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
}
