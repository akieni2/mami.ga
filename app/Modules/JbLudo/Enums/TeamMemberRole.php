<?php

namespace App\Modules\JbLudo\Enums;

enum TeamMemberRole: string
{
    case Player = 'player';
    case Captain = 'captain';
    case Manager = 'manager';
    case Coach = 'coach';
    case Referee = 'referee';
    case Commissioner = 'commissioner';
}
