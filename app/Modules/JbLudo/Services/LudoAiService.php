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

        $dice = (int) ($board['dice'] ?? 0);
        $pieces = $board['players'][$color]['pieces'] ?? [];
        $best = $legal[0];
        $bestScore = PHP_INT_MIN;

        foreach ($legal as $index) {
            $score = $this->scoreMove($board, $color, $index, $dice, $pieces, $difficulty);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $index;
            }
        }

        return $best;
    }

    /**
     * @param  array<string, mixed>  $board
     * @param  array<int, mixed>  $pieces
     */
    private function scoreMove(
        array $board,
        string $color,
        int $pieceIndex,
        int $dice,
        array $pieces,
        string $difficulty,
    ): int {
        $current = (int) ($pieces[$pieceIndex] ?? -1);
        $target = $current < 0 ? 0 : $current + $dice;
        $score = $target * 2;

        // Sortir de la maison avec un 6.
        if ($current < 0 && $dice === 6) {
            $score += 120;
        }

        // Entrer / avancer dans le couloir d'arrivée.
        if ($current < 52 && $target >= 52) {
            $score += 80;
        }
        if ($target >= LudoEngineService::FINISH_POSITION) {
            $score += 200;
        }

        // Capturer un adversaire hors case sûre.
        if ($target >= 0 && $target < LudoEngineService::TRACK_LENGTH) {
            $global = $this->engine->globalTrackPosition($color, $target);
            if ($this->engine->isSafeSquare($global) === false
                && $this->countOpponentsOnGlobal($board, $color, $global) > 0
            ) {
                $score += $difficulty === 'hard' ? 150 : 90;
            }

            // Préférer une case sûre ; éviter une case dangereuse.
            if ($this->engine->isSafeSquare($global)) {
                $score += 35;
            } elseif ($this->isThreatened($board, $color, $global)) {
                $score -= $difficulty === 'hard' ? 70 : 40;
            }
        }

        // Préférer avancer un pion déjà sorti plutôt que stagner.
        if ($current >= 0) {
            $score += 10 + min(20, $current);
        }

        return $score;
    }

    /**
     * @param  array<string, mixed>  $board
     */
    private function isThreatened(array $board, string $color, int $global): bool
    {
        foreach (['red', 'blue', 'green', 'yellow'] as $opponent) {
            if ($opponent === $color) {
                continue;
            }
            $pieces = $board['players'][$opponent]['pieces'] ?? [];
            if (! is_array($pieces)) {
                continue;
            }
            foreach ($pieces as $position) {
                $position = (int) $position;
                if ($position < 0 || $position >= LudoEngineService::TRACK_LENGTH) {
                    continue;
                }
                $oppGlobal = $this->engine->globalTrackPosition($opponent, $position);
                for ($d = 1; $d <= 6; $d++) {
                    if (($oppGlobal + $d) % LudoEngineService::TRACK_LENGTH === $global) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $board
     */
    private function countOpponentsOnGlobal(array $board, string $color, int $global): int
    {
        $count = 0;
        foreach (['red', 'blue', 'green', 'yellow'] as $opponent) {
            if ($opponent === $color) {
                continue;
            }
            $pieces = $board['players'][$opponent]['pieces'] ?? [];
            if (! is_array($pieces)) {
                continue;
            }
            foreach ($pieces as $position) {
                $position = (int) $position;
                if ($position >= 0
                    && $position < LudoEngineService::TRACK_LENGTH
                    && $this->engine->globalTrackPosition($opponent, $position) === $global
                ) {
                    $count++;
                }
            }
        }

        return $count;
    }
}
