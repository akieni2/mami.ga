<?php

namespace App\Modules\JbLudo\Enums;

enum PieceColor: string
{
    case White = 'white';
    case Black = 'black';

    public function opposite(): self
    {
        return $this === self::White ? self::Black : self::White;
    }
}
