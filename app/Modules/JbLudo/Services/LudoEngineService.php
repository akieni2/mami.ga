<?php

namespace App\Modules\JbLudo\Services;

use InvalidArgumentException;

class LudoEngineService
{
    public const PIECES_PER_PLAYER = 4;
    public const TRACK_LENGTH = 52;
    public const FINISH_LENGTH = 6;
    public const FINISH_POSITION = self::TRACK_LENGTH + self::FINISH_LENGTH - 1;

    private const COLORS = ['red', 'blue', 'green', 'yellow'];
    private const START_OFFSETS = [
        'red' => 0,
        'blue' => 13,
        'green' => 26,
        'yellow' => 39,
    ];
    private const SAFE_GLOBAL_SQUARES = [0, 8, 13, 21, 26, 34, 39, 47];

    /**
     * @return array<string, mixed>
     */
    public function initialBoard(): array
    {
        return [
            'type' => 'ludo',
            'players' => [
                'red' => ['label' => 'Rouge', 'pieces' => array_fill(0, self::PIECES_PER_PLAYER, -1)],
                'blue' => ['label' => 'Bleu', 'pieces' => array_fill(0, self::PIECES_PER_PLAYER, -1)],
                'green' => ['label' => 'Vert', 'pieces' => array_fill(0, self::PIECES_PER_PLAYER, -1)],
                'yellow' => ['label' => 'Jaune', 'pieces' => array_fill(0, self::PIECES_PER_PLAYER, -1)],
            ],
            'turn' => 'red',
            'dice' => null,
            'must_roll' => true,
            'winner' => null,
            'log' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $board
     * @return array<string, mixed>
     */
    public function rollDice(array $board, string $color): array
    {
        $this->assertColor($color);
        $this->assertTurn($board, $color);

        if (($board['must_roll'] ?? true) === false) {
            throw new InvalidArgumentException('Vous devez jouer le de deja lance.');
        }

        $dice = random_int(1, 6);
        $board['dice'] = $dice;
        $board['must_roll'] = false;
        $this->pushLog($board, $color, 'roll', $dice);

        if ($this->legalPieces($board, $color) === []) {
            $board['must_roll'] = true;
            $board['dice'] = null;
            $board['skip_turn'] = true;
            $board['turn'] = $this->nextColor($color);
            $this->pushLog($board, $color, 'skip', $dice);
        }

        return $board;
    }

    /**
     * @param  array<string, mixed>  $board
     * @return array{board: array<string, mixed>, result: ?string, extra_turn: bool}
     */
    public function applyMove(array $board, string $color, int $pieceIndex): array
    {
        $this->assertColor($color);
        $this->assertTurn($board, $color);

        if (($board['must_roll'] ?? true) === true || ! isset($board['dice'])) {
            throw new InvalidArgumentException('Lancez le de avant de jouer.');
        }

        if ($pieceIndex < 0 || $pieceIndex >= self::PIECES_PER_PLAYER) {
            throw new InvalidArgumentException('Pion Ludo invalide.');
        }

        $dice = (int) $board['dice'];
        $pieces = $board['players'][$color]['pieces'] ?? null;
        if (! is_array($pieces)) {
            throw new InvalidArgumentException('Etat Ludo invalide.');
        }

        if (! in_array($pieceIndex, $this->legalPieces($board, $color), true)) {
            throw new InvalidArgumentException('Ce pion ne peut pas jouer ce de.');
        }

        $current = (int) $pieces[$pieceIndex];
        $from = $current;
        $pieces[$pieceIndex] = $current < 0 ? 0 : $current + $dice;
        $to = (int) $pieces[$pieceIndex];
        $board['players'][$color]['pieces'] = $pieces;
        $board = $this->captureOpponents($board, $color, $pieceIndex);
        $board['dice'] = null;
        $board['must_roll'] = true;
        unset($board['skip_turn']);
        $this->pushLog($board, $color, 'move', $dice, $pieceIndex, $from, $to);

        $winner = $this->finished($pieces) ? $color : null;
        $board['winner'] = $winner;
        if ($winner === null) {
            $board['turn'] = $dice === 6 ? $color : $this->nextColor($color);
        }

        return [
            'board' => $board,
            'result' => $winner !== null ? $winner.'_win' : null,
            'extra_turn' => $dice === 6 && $winner === null,
        ];
    }

    /**
     * @param  array<string, mixed>  $board
     */
    private function pushLog(
        array &$board,
        string $color,
        string $type,
        ?int $dice = null,
        ?int $piece = null,
        ?int $from = null,
        ?int $to = null,
    ): void {
        $log = is_array($board['log'] ?? null) ? $board['log'] : [];
        $log[] = [
            'color' => $color,
            'type' => $type,
            'dice' => $dice,
            'piece' => $piece,
            'from' => $from,
            'to' => $to,
            't' => time(),
        ];
        $board['log'] = array_values(array_slice($log, -24));
    }

    /**
     * @param  array<string, mixed>  $board
     * @return list<int>
     */
    public function legalPieces(array $board, string $color): array
    {
        $this->assertColor($color);

        $dice = (int) ($board['dice'] ?? 0);
        $pieces = $board['players'][$color]['pieces'] ?? [];
        if ($dice <= 0 || ! is_array($pieces)) {
            return [];
        }

        $legal = [];
        foreach ($pieces as $index => $position) {
            $position = (int) $position;
            if ($position < 0 && $dice !== 6) {
                continue;
            }
            if ($position >= self::FINISH_POSITION) {
                continue;
            }
            if ($position >= 0 && $position + $dice > self::FINISH_POSITION) {
                continue;
            }
            $legal[] = (int) $index;
        }

        return $legal;
    }

    /**
     * @param  list<int>  $pieces
     */
    private function finished(array $pieces): bool
    {
        $target = self::FINISH_POSITION;

        foreach ($pieces as $position) {
            if ((int) $position < $target) {
                return false;
            }
        }

        return true;
    }

    private function assertColor(string $color): void
    {
        if (! in_array($color, self::COLORS, true)) {
            throw new InvalidArgumentException('Couleur Ludo invalide.');
        }
    }

    private function assertTurn(array $board, string $color): void
    {
        if (($board['turn'] ?? 'red') !== $color) {
            throw new InvalidArgumentException('Ce n\'est pas votre tour Ludo.');
        }
    }

    private function nextColor(string $color): string
    {
        $index = array_search($color, self::COLORS, true);

        return self::COLORS[((int) $index + 1) % count(self::COLORS)];
    }

    private function captureOpponents(array $board, string $color, int $pieceIndex): array
    {
        $position = (int) ($board['players'][$color]['pieces'][$pieceIndex] ?? -1);
        if ($position < 0 || $position >= self::TRACK_LENGTH) {
            return $board;
        }

        $global = $this->globalTrackPosition($color, $position);
        if ($this->isSafeSquare($global)) {
            return $board;
        }

        foreach (self::COLORS as $opponent) {
            if ($opponent === $color) {
                continue;
            }

            $pieces = $board['players'][$opponent]['pieces'] ?? [];
            if (! is_array($pieces)) {
                continue;
            }

            foreach ($pieces as $index => $opponentPosition) {
                $opponentPosition = (int) $opponentPosition;
                if ($opponentPosition >= 0
                    && $opponentPosition < self::TRACK_LENGTH
                    && $this->globalTrackPosition($opponent, $opponentPosition) === $global
                ) {
                    $pieces[$index] = -1;
                }
            }
            $board['players'][$opponent]['pieces'] = $pieces;
        }

        return $board;
    }

    public function globalTrackPosition(string $color, int $relativePosition): int
    {
        return (self::START_OFFSETS[$color] + $relativePosition) % self::TRACK_LENGTH;
    }

    public function isSafeSquare(int $global): bool
    {
        return in_array($global, self::SAFE_GLOBAL_SQUARES, true);
    }
}
