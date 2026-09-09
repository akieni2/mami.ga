<?php

namespace App\Modules\JbLudo\Services;

class LudoAiService
{
    public function __construct(
        private readonly LudoEngineService $engine,
    ) {}

    /**
     * @param  array<string, mixed>  $board
     */
    public function choosePiece(array $board, string $color, string $difficulty = 'medium'): ?int
    {
        $legal = $this->engine->legalPieces($board, $color);
        if ($legal === []) {
            return null;
        }

        if ($difficulty === 'beginner') {
            return $legal[array_rand($legal)];
        }

        $pieces = $board['players'][$color]['pieces'] ?? [];
        usort($legal, fn (int $a, int $b): int => ((int) ($pieces[$b] ?? -1)) <=> ((int) ($pieces[$a] ?? -1)));

        return $legal[0];
    }
}
