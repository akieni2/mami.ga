<?php

namespace App\Modules\JbLudo\Services;

use App\Modules\JbLudo\Enums\PieceColor;
use InvalidArgumentException;

/**
 * Moteur dames internationales 10×10 (autorité serveur).
 *
 * Représentation : grille 10×10, cases jouables (r+c) impair.
 * Pièce : ['c' => 'w'|'b', 'k' => bool]
 */
class CheckersEngineService
{
    public const SIZE = 10;

    /**
     * @return list<list<array{c: string, k: bool}|null>>
     */
    public function initialBoard(): array
    {
        $board = array_fill(0, self::SIZE, array_fill(0, self::SIZE, null));

        for ($r = 0; $r < self::SIZE; $r++) {
            for ($c = 0; $c < self::SIZE; $c++) {
                if (! $this->isPlayable($r, $c)) {
                    continue;
                }
                if ($r <= 3) {
                    $board[$r][$c] = ['c' => 'b', 'k' => false];
                } elseif ($r >= 6) {
                    $board[$r][$c] = ['c' => 'w', 'k' => false];
                }
            }
        }

        return $board;
    }

    /**
     * @param  list<list<array{c: string, k: bool}|null>>  $board
     * @param  list<array{r: int, c: int}>  $path
     * @return array{
     *     board: list<list<array{c: string, k: bool}|null>>,
     *     captures: list<array{r: int, c: int}>,
     *     became_king: bool,
     *     result: ?string
     * }
     */
    public function applyMove(array $board, PieceColor $color, array $path): array
    {
        if (count($path) < 2) {
            throw new InvalidArgumentException('Un coup doit contenir au moins deux cases.');
        }

        foreach ($path as $square) {
            if (! isset($square['r'], $square['c']) || ! $this->inBounds((int) $square['r'], (int) $square['c'])) {
                throw new InvalidArgumentException('Case hors damier.');
            }
        }

        $legal = $this->legalMoves($board, $color);
        $normalized = $this->normalizePath($path);
        $matched = null;

        foreach ($legal as $candidate) {
            if ($this->normalizePath($candidate['path']) === $normalized) {
                $matched = $candidate;
                break;
            }
        }

        if ($matched === null) {
            throw new InvalidArgumentException('Coup illégal ou prise obligatoire non respectée.');
        }

        $newBoard = $matched['board'];
        $result = $this->detectTerminal($newBoard, $color->opposite());

        return [
            'board' => $newBoard,
            'captures' => $matched['captures'],
            'became_king' => $matched['became_king'],
            'result' => $result,
        ];
    }

    /**
     * @param  list<list<array{c: string, k: bool}|null>>  $board
     * @return list<array{
     *     path: list<array{r: int, c: int}>,
     *     captures: list<array{r: int, c: int}>,
     *     board: list<list<array{c: string, k: bool}|null>>,
     *     became_king: bool
     * }>
     */
    public function legalMoves(array $board, PieceColor $color): array
    {
        $captures = $this->allCaptureSequences($board, $color);

        if ($captures !== []) {
            $maxLen = max(array_map(fn ($m) => count($m['captures']), $captures));

            return array_values(array_filter($captures, fn ($m) => count($m['captures']) === $maxLen));
        }

        return $this->quietMoves($board, $color);
    }

    /**
     * @param  list<list<array{c: string, k: bool}|null>>  $board
     */
    public function hasLegalMove(array $board, PieceColor $color): bool
    {
        return $this->legalMoves($board, $color) !== [];
    }

    /**
     * @param  list<list<array{c: string, k: bool}|null>>  $board
     */
    public function detectTerminal(array $board, PieceColor $sideToMove): ?string
    {
        $whiteCount = $this->countPieces($board, PieceColor::White);
        $blackCount = $this->countPieces($board, PieceColor::Black);

        if ($whiteCount === 0) {
            return 'black_win';
        }
        if ($blackCount === 0) {
            return 'white_win';
        }
        if (! $this->hasLegalMove($board, $sideToMove)) {
            return $sideToMove === PieceColor::White ? 'black_win' : 'white_win';
        }

        return null;
    }

    /**
     * @param  list<list<array{c: string, k: bool}|null>>  $board
     * @return list<array{
     *     path: list<array{r: int, c: int}>,
     *     captures: list<array{r: int, c: int}>,
     *     board: list<list<array{c: string, k: bool}|null>>,
     *     became_king: bool
     * }>
     */
    private function quietMoves(array $board, PieceColor $color): array
    {
        $moves = [];
        $code = $color === PieceColor::White ? 'w' : 'b';
        $forward = $color === PieceColor::White ? -1 : 1;

        for ($r = 0; $r < self::SIZE; $r++) {
            for ($c = 0; $c < self::SIZE; $c++) {
                $piece = $board[$r][$c];
                if ($piece === null || $piece['c'] !== $code) {
                    continue;
                }

                $dirs = $piece['k']
                    ? [[-1, -1], [-1, 1], [1, -1], [1, 1]]
                    : [[$forward, -1], [$forward, 1]];

                foreach ($dirs as [$dr, $dc]) {
                    if ($piece['k']) {
                        for ($step = 1; $step < self::SIZE; $step++) {
                            $nr = $r + $dr * $step;
                            $nc = $c + $dc * $step;
                            if (! $this->inBounds($nr, $nc) || ! $this->isPlayable($nr, $nc)) {
                                break;
                            }
                            if ($board[$nr][$nc] !== null) {
                                break;
                            }
                            $moves[] = $this->buildQuietResult($board, $r, $c, $nr, $nc, $color);
                        }
                    } else {
                        $nr = $r + $dr;
                        $nc = $c + $dc;
                        if ($this->inBounds($nr, $nc) && $this->isPlayable($nr, $nc) && $board[$nr][$nc] === null) {
                            $moves[] = $this->buildQuietResult($board, $r, $c, $nr, $nc, $color);
                        }
                    }
                }
            }
        }

        return $moves;
    }

    /**
     * @param  list<list<array{c: string, k: bool}|null>>  $board
     * @return array{
     *     path: list<array{r: int, c: int}>,
     *     captures: list<array{r: int, c: int}>,
     *     board: list<list<array{c: string, k: bool}|null>>,
     *     became_king: bool
     * }
     */
    private function buildQuietResult(array $board, int $r, int $c, int $nr, int $nc, PieceColor $color): array
    {
        $newBoard = $board;
        $piece = $newBoard[$r][$c];
        $newBoard[$r][$c] = null;
        $becameKing = false;

        if (! $piece['k'] && $this->isPromotionRow($nr, $color)) {
            $piece['k'] = true;
            $becameKing = true;
        }

        $newBoard[$nr][$nc] = $piece;

        return [
            'path' => [['r' => $r, 'c' => $c], ['r' => $nr, 'c' => $nc]],
            'captures' => [],
            'board' => $newBoard,
            'became_king' => $becameKing,
        ];
    }

    /**
     * @param  list<list<array{c: string, k: bool}|null>>  $board
     * @return list<array{
     *     path: list<array{r: int, c: int}>,
     *     captures: list<array{r: int, c: int}>,
     *     board: list<list<array{c: string, k: bool}|null>>,
     *     became_king: bool
     * }>
     */
    private function allCaptureSequences(array $board, PieceColor $color): array
    {
        $results = [];
        $code = $color === PieceColor::White ? 'w' : 'b';

        for ($r = 0; $r < self::SIZE; $r++) {
            for ($c = 0; $c < self::SIZE; $c++) {
                $piece = $board[$r][$c];
                if ($piece === null || $piece['c'] !== $code) {
                    continue;
                }
                $this->dfsCaptures(
                    $board,
                    $r,
                    $c,
                    $piece,
                    $color,
                    [['r' => $r, 'c' => $c]],
                    [],
                    [],
                    $results,
                    false,
                );
            }
        }

        return $results;
    }

    /**
     * @param  list<list<array{c: string, k: bool}|null>>  $board
     * @param  array{c: string, k: bool}  $piece
     * @param  list<array{r: int, c: int}>  $path
     * @param  list<array{r: int, c: int}>  $captures
     * @param  list<string>  $capturedKeys
     * @param  list<array{
     *     path: list<array{r: int, c: int}>,
     *     captures: list<array{r: int, c: int}>,
     *     board: list<list<array{c: string, k: bool}|null>>,
     *     became_king: bool
     * }>  $results
     */
    private function dfsCaptures(
        array $board,
        int $r,
        int $c,
        array $piece,
        PieceColor $color,
        array $path,
        array $captures,
        array $capturedKeys,
        array &$results,
        bool $becameKing,
    ): void {
        $jumps = $this->possibleJumpsFrom($board, $r, $c, $piece, $color, $capturedKeys);

        if ($jumps === [] && $captures !== []) {
            $finalBoard = $board;
            $finalPiece = $piece;
            $promo = false;
            if (! $finalPiece['k'] && $this->isPromotionRow($r, $color)) {
                $finalPiece['k'] = true;
                $promo = true;
            }
            $finalBoard[$r][$c] = $finalPiece;
            $results[] = [
                'path' => $path,
                'captures' => $captures,
                'board' => $finalBoard,
                'became_king' => $becameKing || $promo,
            ];

            return;
        }

        foreach ($jumps as $jump) {
            $nextBoard = $board;
            $nextBoard[$r][$c] = null;
            $nextBoard[$jump['capture']['r']][$jump['capture']['c']] = null;
            $nextPiece = $piece;
            $promoNow = false;
            // International: promote only at end of capture sequence unless already king
            $nextBoard[$jump['to']['r']][$jump['to']['c']] = $nextPiece;

            $nextKeys = $capturedKeys;
            $nextKeys[] = $jump['capture']['r'].':'.$jump['capture']['c'];

            $this->dfsCaptures(
                $nextBoard,
                $jump['to']['r'],
                $jump['to']['c'],
                $nextPiece,
                $color,
                array_merge($path, [['r' => $jump['to']['r'], 'c' => $jump['to']['c']]]),
                array_merge($captures, [$jump['capture']]),
                $nextKeys,
                $results,
                $becameKing || $promoNow,
            );
        }
    }

    /**
     * @param  list<list<array{c: string, k: bool}|null>>  $board
     * @param  array{c: string, k: bool}  $piece
     * @param  list<string>  $capturedKeys
     * @return list<array{to: array{r: int, c: int}, capture: array{r: int, c: int}}>
     */
    private function possibleJumpsFrom(
        array $board,
        int $r,
        int $c,
        array $piece,
        PieceColor $color,
        array $capturedKeys,
    ): array {
        $opp = $color === PieceColor::White ? 'b' : 'w';
        $jumps = [];
        $dirs = [[-1, -1], [-1, 1], [1, -1], [1, 1]];

        foreach ($dirs as [$dr, $dc]) {
            if ($piece['k']) {
                $enemyR = null;
                $enemyC = null;
                for ($step = 1; $step < self::SIZE; $step++) {
                    $nr = $r + $dr * $step;
                    $nc = $c + $dc * $step;
                    if (! $this->inBounds($nr, $nc) || ! $this->isPlayable($nr, $nc)) {
                        break;
                    }
                    $cell = $board[$nr][$nc];
                    if ($enemyR === null) {
                        if ($cell === null) {
                            continue;
                        }
                        if ($cell['c'] !== $opp) {
                            break;
                        }
                        $key = $nr.':'.$nc;
                        if (in_array($key, $capturedKeys, true)) {
                            break;
                        }
                        $enemyR = $nr;
                        $enemyC = $nc;
                        continue;
                    }

                    if ($cell !== null) {
                        break;
                    }

                    $jumps[] = [
                        'to' => ['r' => $nr, 'c' => $nc],
                        'capture' => ['r' => $enemyR, 'c' => $enemyC],
                    ];
                }
            } else {
                $er = $r + $dr;
                $ec = $c + $dc;
                $lr = $r + 2 * $dr;
                $lc = $c + 2 * $dc;
                if (! $this->inBounds($er, $ec) || ! $this->inBounds($lr, $lc)) {
                    continue;
                }
                if (! $this->isPlayable($er, $ec) || ! $this->isPlayable($lr, $lc)) {
                    continue;
                }
                $enemy = $board[$er][$ec];
                if ($enemy === null || $enemy['c'] !== $opp) {
                    continue;
                }
                $key = $er.':'.$ec;
                if (in_array($key, $capturedKeys, true)) {
                    continue;
                }
                if ($board[$lr][$lc] !== null) {
                    continue;
                }
                $jumps[] = [
                    'to' => ['r' => $lr, 'c' => $lc],
                    'capture' => ['r' => $er, 'c' => $ec],
                ];
            }
        }

        return $jumps;
    }

    /**
     * @param  list<list<array{c: string, k: bool}|null>>  $board
     */
    private function countPieces(array $board, PieceColor $color): int
    {
        $code = $color === PieceColor::White ? 'w' : 'b';
        $count = 0;
        foreach ($board as $row) {
            foreach ($row as $cell) {
                if ($cell !== null && $cell['c'] === $code) {
                    $count++;
                }
            }
        }

        return $count;
    }

    private function isPromotionRow(int $r, PieceColor $color): bool
    {
        return $color === PieceColor::White ? $r === 0 : $r === self::SIZE - 1;
    }

    private function isPlayable(int $r, int $c): bool
    {
        return ($r + $c) % 2 === 1;
    }

    private function inBounds(int $r, int $c): bool
    {
        return $r >= 0 && $r < self::SIZE && $c >= 0 && $c < self::SIZE;
    }

    /**
     * @param  list<array{r: int, c: int}>  $path
     */
    private function normalizePath(array $path): string
    {
        return collect($path)
            ->map(fn ($s) => ((int) $s['r']).','.((int) $s['c']))
            ->implode(';');
    }
}
