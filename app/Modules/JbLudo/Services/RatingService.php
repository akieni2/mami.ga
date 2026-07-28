<?php

namespace App\Modules\JbLudo\Services;

use App\Modules\JbLudo\Models\GameMatch;
use App\Modules\JbLudo\Models\PlayerProfile;
use App\Modules\JbLudo\Models\RatingEvent;
use Illuminate\Support\Facades\DB;

class RatingService
{
    public function applyMatchResult(GameMatch $match): void
    {
        if ($match->whitePlayer === null || $match->blackPlayer === null) {
            return;
        }

        DB::transaction(function () use ($match): void {
            $white = $match->whitePlayer()->lockForUpdate()->first();
            $black = $match->blackPlayer()->lockForUpdate()->first();

            if ($white === null || $black === null) {
                return;
            }

            $cfg = config('mami.jb_ludo');

            if ($match->result?->value === 'draw') {
                $this->credit($white, $match, 'draw', (int) $cfg['points_draw']);
                $this->credit($black, $match, 'draw', (int) $cfg['points_draw']);
                $white->increment('games_drawn');
                $black->increment('games_drawn');
            } elseif ((int) $match->winner_id === (int) $white->id) {
                $this->credit($white, $match, 'win', (int) $cfg['points_win']);
                $this->credit($black, $match, $match->result_reason === 'resign' ? 'resign' : 'loss',
                    $match->result_reason === 'resign' ? (int) $cfg['points_resign'] : (int) $cfg['points_loss']);
                $white->increment('games_won');
                $black->increment('games_lost');
                if ($match->result_reason === 'resign') {
                    $black->increment('resign_count');
                }
            } else {
                $this->credit($black, $match, 'win', (int) $cfg['points_win']);
                $this->credit($white, $match, $match->result_reason === 'resign' ? 'resign' : 'loss',
                    $match->result_reason === 'resign' ? (int) $cfg['points_resign'] : (int) $cfg['points_loss']);
                $black->increment('games_won');
                $white->increment('games_lost');
                if ($match->result_reason === 'resign') {
                    $white->increment('resign_count');
                }
            }

            $white->increment('games_played');
            $black->increment('games_played');
        });
    }

    private function credit(PlayerProfile $player, GameMatch $match, string $eventType, int $delta): void
    {
        $player->points = (int) $player->points + $delta;
        $player->save();

        RatingEvent::query()->create([
            'player_id' => $player->id,
            'match_id' => $match->id,
            'event_type' => $eventType,
            'delta' => $delta,
            'points_after' => $player->points,
            'created_at' => now(),
        ]);
    }
}
