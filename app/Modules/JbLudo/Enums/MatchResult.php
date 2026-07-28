<?php

namespace App\Modules\JbLudo\Enums;

enum MatchResult: string
{
    case WhiteWin = 'white_win';
    case BlackWin = 'black_win';
    case Draw = 'draw';
}
