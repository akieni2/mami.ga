<?php

namespace Tests\Unit\JbLudo;

use App\Modules\JbLudo\Enums\PieceColor;
use App\Modules\JbLudo\Services\CheckersEngineService;
use PHPUnit\Framework\TestCase;

class CheckersEngineTest extends TestCase
{
    private CheckersEngineService $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new CheckersEngineService;
    }

    public function test_initial_board_has_twenty_pieces_each(): void
    {
        $board = $this->engine->initialBoard();
        $white = 0;
        $black = 0;

        foreach ($board as $row) {
            foreach ($row as $cell) {
                if ($cell === null) {
                    continue;
                }
                if ($cell['c'] === 'w') {
                    $white++;
                }
                if ($cell['c'] === 'b') {
                    $black++;
                }
            }
        }

        $this->assertSame(20, $white);
        $this->assertSame(20, $black);
    }

    public function test_white_has_quiet_opening_moves(): void
    {
        $board = $this->engine->initialBoard();
        $moves = $this->engine->legalMoves($board, PieceColor::White);

        $this->assertNotEmpty($moves);
        $this->assertTrue(collect($moves)->every(fn ($m) => $m['captures'] === []));
    }

    public function test_simple_forward_move_applies(): void
    {
        $board = $this->engine->initialBoard();
        // White man on row 6, col 1 can move to row 5, col 0 or col 2
        $path = [
            ['r' => 6, 'c' => 1],
            ['r' => 5, 'c' => 0],
        ];

        $result = $this->engine->applyMove($board, PieceColor::White, $path);

        $this->assertNull($result['board'][6][1]);
        $this->assertNotNull($result['board'][5][0]);
        $this->assertSame('w', $result['board'][5][0]['c']);
        $this->assertSame([], $result['captures']);
    }

    public function test_illegal_move_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $board = $this->engine->initialBoard();
        $this->engine->applyMove($board, PieceColor::White, [
            ['r' => 6, 'c' => 1],
            ['r' => 4, 'c' => 1],
        ]);
    }

    public function test_capture_is_mandatory_when_available(): void
    {
        $board = array_fill(0, 10, array_fill(0, 10, null));
        $board[5][4] = ['c' => 'w', 'k' => false];
        $board[4][3] = ['c' => 'b', 'k' => false];

        $moves = $this->engine->legalMoves($board, PieceColor::White);

        $this->assertNotEmpty($moves);
        $this->assertTrue(collect($moves)->every(fn ($m) => count($m['captures']) >= 1));
    }
}
