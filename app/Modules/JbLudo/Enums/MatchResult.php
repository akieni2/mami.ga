<?php

namespace App\Modules\JbLudo\Enums;

enum MatchResult: string
{
    case WhiteWin = 'white_win';
    case BlackWin = 'black_win';
    case RedWin = 'red_win';
    case BlueWin = 'blue_win';
    case GreenWin = 'green_win';
    case YellowWin = 'yellow_win';
    case Draw = 'draw';
}
