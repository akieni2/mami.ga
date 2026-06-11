<?php

namespace App\Enums;

enum LocationSource: string
{
    case Text = 'text';
    case Map = 'map';
    case Hybrid = 'hybrid';
}
