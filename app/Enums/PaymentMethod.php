<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case AirtelMoney = 'airtel_money';
    case MoovMoney = 'moov_money';
    case Card = 'card';
}
