<?php

namespace App\Modules\JbLudo\Services;

use App\Modules\JbLudo\Enums\PieceColor;

class CheckersAiService
{
    public function __construct(
        private readonly CheckersEngineService $engine,
    ) {}

    /**
     * @param  list<list<array{c: string, k: bool}|null>>  $board
     * @return list<array{r: int, c: int}>
     */
    public function chooseMove(array $board, PieceColor $color, string $difficulty = 'medium'): array
    {
        $moves = $this->engine->legalMoves($board, $color);
        if ($moves === []) {
            return [];
        }

        if ($difficulty === 'beginner') {
            return $moves[array_rand($moves)]['path'];
        }

        usort($moves, function (array $a, array $b): int {
            $captures = count($b['captures']) <=> count($a['captures']);

            return $captures !== 0 ? $captures : (($b['became_king'] ? 1 : 0) <=> ($a['became_king'] ? 1 : 0));
        });

        return $moves[0]['path'];
    }
}
