<?php

namespace Tests\Unit\JbLudo;

use App\Modules\JbLudo\Services\LudoEngineService;
use PHPUnit\Framework\TestCase;

class LudoEngineTest extends TestCase
{
    private LudoEngineService $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new LudoEngineService;
    }

    public function test_initial_board_has_four_pieces_at_home_for_each_player(): void
    {
        $board = $this->engine->initialBoard();

        $this->assertSame([-1, -1, -1, -1], $board['players']['red']['pieces']);
        $this->assertSame([-1, -1, -1, -1], $board['players']['blue']['pieces']);
        $this->assertSame([-1, -1, -1, -1], $board['players']['green']['pieces']);
        $this->assertSame([-1, -1, -1, -1], $board['players']['yellow']['pieces']);
        $this->assertSame('red', $board['turn']);
        $this->assertTrue($board['must_roll']);
    }

    public function test_piece_can_only_leave_home_on_six(): void
    {
        $board = $this->engine->initialBoard();
        $board['dice'] = 5;
        $board['must_roll'] = false;

        $this->assertSame([], $this->engine->legalPieces($board, 'red'));

        $board['dice'] = 6;
        $this->assertSame([0, 1, 2, 3], $this->engine->legalPieces($board, 'red'));
    }

    public function test_piece_leaves_home_on_six(): void
    {
        $board = $this->engine->initialBoard();
        $board['dice'] = 6;
        $board['must_roll'] = false;

        $result = $this->engine->applyMove($board, 'red', 0);

        $this->assertSame(0, $result['board']['players']['red']['pieces'][0]);
        $this->assertTrue($result['extra_turn']);
        $this->assertNull($result['result']);
    }

    public function test_player_wins_when_all_pieces_reach_finish(): void
    {
        $board = $this->engine->initialBoard();
        $board['players']['red']['pieces'] = [57, 57, 57, 56];
        $board['dice'] = 1;
        $board['must_roll'] = false;

        $result = $this->engine->applyMove($board, 'red', 3);

        $this->assertSame('red_win', $result['result']);
        $this->assertSame('red', $result['board']['winner']);
    }

    public function test_turn_moves_to_next_color_without_six(): void
    {
        $board = $this->engine->initialBoard();
        $board['players']['red']['pieces'] = [0, -1, -1, -1];
        $board['dice'] = 3;
        $board['must_roll'] = false;

        $result = $this->engine->applyMove($board, 'red', 0);

        $this->assertSame('blue', $result['board']['turn']);
    }

    public function test_landing_on_opponent_captures_piece_outside_safe_square(): void
    {
        $board = $this->engine->initialBoard();
        $board['players']['red']['pieces'] = [4, -1, -1, -1];
        $board['players']['blue']['pieces'] = [48, -1, -1, -1];
        $board['dice'] = 5;
        $board['must_roll'] = false;

        $result = $this->engine->applyMove($board, 'red', 0);

        $this->assertSame(9, $result['board']['players']['red']['pieces'][0]);
        $this->assertSame(-1, $result['board']['players']['blue']['pieces'][0]);
    }
}
