<?php

namespace App\Modules\JbLudo\Services;

use App\Modules\JbLudo\Enums\GameMode;
use App\Modules\JbLudo\Enums\MatchResult;
use App\Modules\JbLudo\Enums\MatchStatus;
use App\Modules\JbLudo\Enums\PieceColor;
use App\Modules\JbLudo\Events\JbMatchUpdated;
use App\Modules\JbLudo\Models\GameMatch;
use App\Modules\JbLudo\Models\GameMove;
use App\Modules\JbLudo\Models\PlayerProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MatchLifecycleService
{
    public function __construct(
        private readonly CheckersEngineService $engine,
        private readonly RatingService $rating,
    ) {}

    public function createMatch(PlayerProfile $white, PlayerProfile $black, GameMode $mode): GameMatch
    {
        $clock = (int) config('mami.jb_ludo.default_clock_seconds', 600);

        $match = GameMatch::query()->create([
            'reference' => $this->nextReference(),
            'mode' => $mode,
            'status' => MatchStatus::InProgress,
            'white_player_id' => $white->id,
            'black_player_id' => $black->id,
            'turn_color' => PieceColor::White,
            'board_state' => $this->engine->initialBoard(),
            'clock_seconds' => $clock,
            'white_time_left' => $clock,
            'black_time_left' => $clock,
            'turn_started_at' => now(),
            'started_at' => now(),
        ]);

        event(new JbMatchUpdated($match, 'MatchStarted'));

        return $match->fresh(['whitePlayer', 'blackPlayer']);
    }

    /**
     * @param  list<array{r: int, c: int}>  $path
     */
    public function playMove(GameMatch $match, PlayerProfile $player, array $path): GameMatch
    {
        return DB::transaction(function () use ($match, $player, $path): GameMatch {
            /** @var GameMatch $match */
            $match = GameMatch::query()->lockForUpdate()->findOrFail($match->id);
            $this->assertPlayable($match, $player);
            $this->consumeClock($match);

            $color = $match->playerColor($player);
            if ($color === null || $color !== $match->turn_color) {
                throw ValidationException::withMessages([
                    'turn' => ['Ce n\'est pas votre tour.'],
                ]);
            }

            try {
                $applied = $this->engine->applyMove($match->board_state ?? [], $color, $path);
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::withMessages([
                    'path' => [$e->getMessage()],
                ]);
            }

            $seq = $match->move_count + 1;
            GameMove::query()->create([
                'match_id' => $match->id,
                'server_seq' => $seq,
                'player_id' => $player->id,
                'color' => $color,
                'path' => $path,
                'captures' => $applied['captures'],
                'became_king' => $applied['became_king'],
                'board_after' => $applied['board'],
                'played_at' => now(),
                'created_at' => now(),
            ]);

            $match->update([
                'board_state' => $applied['board'],
                'move_count' => $seq,
                'turn_color' => $color->opposite(),
                'turn_started_at' => now(),
                'status' => MatchStatus::InProgress,
                'grace_until' => null,
                'disconnected_player_id' => null,
            ]);

            $match = $match->fresh(['whitePlayer', 'blackPlayer']);

            if ($applied['result'] !== null) {
                $this->finish($match, MatchResult::from($applied['result']), 'normal');
            } else {
                event(new JbMatchUpdated($match, 'MovePlayed', [
                    'server_seq' => $seq,
                    'path' => $path,
                    'captures' => $applied['captures'],
                ]));
            }

            return $match->fresh(['whitePlayer', 'blackPlayer', 'winner']);
        });
    }

    public function resign(GameMatch $match, PlayerProfile $player): GameMatch
    {
        return DB::transaction(function () use ($match, $player): GameMatch {
            $match = GameMatch::query()->lockForUpdate()->findOrFail($match->id);
            $this->assertPlayable($match, $player, allowGrace: true);

            $color = $match->playerColor($player);
            if ($color === null) {
                throw ValidationException::withMessages(['player' => ['Vous n\'êtes pas dans cette partie.']]);
            }

            $result = $color === PieceColor::White ? MatchResult::BlackWin : MatchResult::WhiteWin;

            return $this->finish($match, $result, 'resign');
        });
    }

    public function markDisconnected(GameMatch $match, PlayerProfile $player): GameMatch
    {
        $grace = (int) config('mami.jb_ludo.reconnect_grace_seconds', 90);

        $match->update([
            'status' => MatchStatus::Grace,
            'disconnected_player_id' => $player->id,
            'grace_until' => now()->addSeconds($grace),
        ]);

        $match = $match->fresh();
        event(new JbMatchUpdated($match, 'PlayerDisconnected', [
            'player_id' => $player->id,
            'grace_until' => $match->grace_until?->toIso8601String(),
        ]));

        return $match;
    }

    public function reconnect(GameMatch $match, PlayerProfile $player): GameMatch
    {
        if ($match->status !== MatchStatus::Grace) {
            return $match;
        }

        if ((int) $match->disconnected_player_id !== (int) $player->id) {
            throw ValidationException::withMessages(['player' => ['Reconnexion non applicable.']]);
        }

        if ($match->grace_until !== null && $match->grace_until->isPast()) {
            $color = $match->playerColor($player);
            $result = $color === PieceColor::White ? MatchResult::BlackWin : MatchResult::WhiteWin;

            return $this->finish($match, $result, 'timeout_disconnect');
        }

        $match->update([
            'status' => MatchStatus::InProgress,
            'disconnected_player_id' => null,
            'grace_until' => null,
            'turn_started_at' => now(),
        ]);

        $match = $match->fresh();
        event(new JbMatchUpdated($match, 'PlayerReconnected', ['player_id' => $player->id]));

        return $match;
    }

    public function resolveExpiredGrace(GameMatch $match): ?GameMatch
    {
        if ($match->status !== MatchStatus::Grace || $match->grace_until === null || $match->grace_until->isFuture()) {
            return null;
        }

        $disconnected = $match->disconnected_player_id;
        if ($disconnected === null) {
            return null;
        }

        $result = (int) $disconnected === (int) $match->white_player_id
            ? MatchResult::BlackWin
            : MatchResult::WhiteWin;

        return $this->finish($match, $result, 'timeout_disconnect');
    }

    private function finish(GameMatch $match, MatchResult $result, string $reason): GameMatch
    {
        $winnerId = match ($result) {
            MatchResult::WhiteWin => $match->white_player_id,
            MatchResult::BlackWin => $match->black_player_id,
            MatchResult::Draw => null,
        };

        $match->update([
            'status' => MatchStatus::Finished,
            'result' => $result,
            'result_reason' => $reason,
            'winner_id' => $winnerId,
            'ended_at' => now(),
            'grace_until' => null,
            'disconnected_player_id' => null,
        ]);

        $match = $match->fresh(['whitePlayer', 'blackPlayer', 'winner']);
        $this->rating->applyMatchResult($match);
        $this->attemptAutoAdvanceChampionship($match);
        event(new JbMatchUpdated($match, 'MatchEnded'));

        return $match->fresh(['whitePlayer', 'blackPlayer', 'winner']);
    }

    private function attemptAutoAdvanceChampionship(GameMatch $match): void
    {
        if ($match->championship_id === null || $match->championship_round === null) {
            return;
        }

        $unfinished = GameMatch::query()
            ->where('championship_id', $match->championship_id)
            ->where('championship_round', $match->championship_round)
            ->where('status', '!=', MatchStatus::Finished)
            ->exists();

        if ($unfinished) {
            return;
        }

        try {
            app(ChampionshipService::class)->generateNextRound($match->championship);
        } catch (\Throwable) {
            // Le bouton admin permet de relancer si une situation metier doit etre tranchee.
        }
    }

    private function assertPlayable(GameMatch $match, PlayerProfile $player, bool $allowGrace = false): void
    {
        $ok = in_array($match->status, [MatchStatus::InProgress, ...($allowGrace ? [MatchStatus::Grace] : [])], true);
        if (! $ok) {
            throw ValidationException::withMessages([
                'status' => ['Cette partie n\'est plus jouable.'],
            ]);
        }

        if ($match->playerColor($player) === null) {
            throw ValidationException::withMessages([
                'player' => ['Vous n\'êtes pas dans cette partie.'],
            ]);
        }
    }

    private function consumeClock(GameMatch $match): void
    {
        if ($match->turn_started_at === null) {
            return;
        }

        $elapsed = max(0, now()->diffInSeconds($match->turn_started_at));
        if ($match->turn_color === PieceColor::White) {
            $left = max(0, (int) $match->white_time_left - $elapsed);
            $match->white_time_left = $left;
            if ($left <= 0) {
                $this->finish($match, MatchResult::BlackWin, 'timeout');
                throw ValidationException::withMessages(['clock' => ['Temps écoulé pour les blancs.']]);
            }
        } else {
            $left = max(0, (int) $match->black_time_left - $elapsed);
            $match->black_time_left = $left;
            if ($left <= 0) {
                $this->finish($match, MatchResult::WhiteWin, 'timeout');
                throw ValidationException::withMessages(['clock' => ['Temps écoulé pour les noirs.']]);
            }
        }
        $match->save();
    }

    private function nextReference(): string
    {
        return 'JB-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }
}
