<?php

namespace App\Modules\JbLudo\Services;

use App\Modules\JbLudo\Enums\GameMode;
use App\Modules\JbLudo\Enums\GameType;
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
        private readonly LudoEngineService $ludo,
        private readonly CheckersAiService $checkersAi,
        private readonly LudoAiService $ludoAi,
        private readonly BotProfileService $bots,
        private readonly RatingService $rating,
    ) {}

    public function createMatch(PlayerProfile $white, PlayerProfile $black, GameMode $mode, GameType $gameType = GameType::Damier): GameMatch
    {
        $clock = (int) config('mami.jb_ludo.default_clock_seconds', 600);

        $match = GameMatch::query()->create([
            'reference' => $this->nextReference(),
            'game_type' => $gameType,
            'mode' => $mode,
            'status' => MatchStatus::InProgress,
            'white_player_id' => $white->id,
            'black_player_id' => $black->id,
            'turn_color' => PieceColor::White,
            'board_state' => $gameType === GameType::Ludo ? $this->ludo->initialBoard() : $this->engine->initialBoard(),
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
     * @param  list<PlayerProfile>  $players
     */
    public function createLudoMatch(array $players, GameMode $mode): GameMatch
    {
        if (count($players) !== 4) {
            throw ValidationException::withMessages([
                'players' => ['Une partie Ludo doit contenir exactement 4 joueurs.'],
            ]);
        }

        $clock = (int) config('mami.jb_ludo.default_clock_seconds', 600);
        $colors = ['red', 'blue', 'green', 'yellow'];
        $ludoPlayerIds = [];
        foreach ($colors as $index => $color) {
            $ludoPlayerIds[$color] = $players[$index]->id;
        }

        $match = GameMatch::query()->create([
            'reference' => $this->nextReference(),
            'game_type' => GameType::Ludo,
            'mode' => $mode,
            'status' => MatchStatus::InProgress,
            'white_player_id' => $players[0]->id,
            'black_player_id' => $players[1]->id,
            'ludo_player_ids' => $ludoPlayerIds,
            'turn_color' => PieceColor::White,
            'board_state' => $this->ludo->initialBoard(),
            'clock_seconds' => $clock,
            'white_time_left' => $clock,
            'black_time_left' => $clock,
            'turn_started_at' => now(),
            'started_at' => now(),
        ]);

        event(new JbMatchUpdated($match, 'MatchStarted'));

        return $match->fresh(['whitePlayer', 'blackPlayer']);
    }

    public function createSoloMatch(PlayerProfile $player, GameType $gameType, string $difficulty = 'medium'): GameMatch
    {
        if ($gameType === GameType::Damier) {
            $bot = $this->bots->profile('damier-'.$difficulty, 'IA Damier '.ucfirst($difficulty));
            return $this->createMatch($player, $bot, GameMode::Solo, GameType::Damier);
        }

        $blue = $this->bots->profile('ludo-blue-'.$difficulty, 'IA Ludo Bleu');
        $green = $this->bots->profile('ludo-green-'.$difficulty, 'IA Ludo Vert');
        $yellow = $this->bots->profile('ludo-yellow-'.$difficulty, 'IA Ludo Jaune');
        $match = $this->createLudoMatch([$player, $blue, $green, $yellow], GameMode::Solo);
        $board = $match->board_state ?? [];
        $board['_ai'] = [
            'difficulty' => $difficulty,
            'bot_player_ids' => [$blue->id, $green->id, $yellow->id],
        ];
        $match->update(['board_state' => $board]);

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

            $color = $match->game_type === GameType::Ludo ? $match->ludoColor($player) : $match->playerColor($player);
            if ($color === null || ($match->game_type !== GameType::Ludo && $color !== $match->turn_color)) {
                throw ValidationException::withMessages([
                    'turn' => ['Ce n\'est pas votre tour.'],
                ]);
            }

            try {
                if ($match->game_type === GameType::Ludo) {
                    $applied = $this->applyLudoAction($match, (string) $color, $path);
                } else {
                    $applied = $this->engine->applyMove($match->board_state ?? [], $color, $path);
                }
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
                'color' => $color instanceof PieceColor ? $color->value : $color,
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
                'turn_color' => $match->game_type === GameType::Ludo
                    ? $match->turn_color
                    : (($applied['extra_turn'] ?? false) ? $color : $color->opposite()),
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

            $match = $match->fresh(['whitePlayer', 'blackPlayer', 'winner']);

            if ($match->mode === GameMode::Solo && $match->status === MatchStatus::InProgress) {
                $this->playSoloAiTurns($match);
            }

            return $match->fresh(['whitePlayer', 'blackPlayer', 'winner', 'moves']);
        });
    }

    public function resign(GameMatch $match, PlayerProfile $player): GameMatch
    {
        return DB::transaction(function () use ($match, $player): GameMatch {
            $match = GameMatch::query()->lockForUpdate()->findOrFail($match->id);
            $this->assertPlayable($match, $player, allowGrace: true);

            if ($match->game_type === GameType::Ludo) {
                return $this->resignLudo($match, $player);
            }

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
        $winnerId = $match->game_type === GameType::Ludo ? $this->ludoWinnerId($match, $result) : match ($result) {
            MatchResult::WhiteWin => $match->white_player_id,
            MatchResult::BlackWin => $match->black_player_id,
            MatchResult::Draw => null,
            default => null,
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

    private function resignLudo(GameMatch $match, PlayerProfile $player): GameMatch
    {
        $players = $match->ludo_player_ids ?? [];
        $remaining = collect($players)
            ->reject(fn ($playerId) => (int) $playerId === (int) $player->id)
            ->values();

        if ($remaining->count() !== 1) {
            throw ValidationException::withMessages([
                'resign' => ['Abandon Ludo disponible seulement lorsqu\'il reste deux joueurs.'],
            ]);
        }

        $winnerColor = collect($players)
            ->filter(fn ($playerId) => (int) $playerId === (int) $remaining->first())
            ->keys()
            ->first();

        return $this->finish($match, MatchResult::from($winnerColor.'_win'), 'resign');
    }

    private function ludoWinnerId(GameMatch $match, MatchResult $result): ?int
    {
        $winnerColor = str_replace('_win', '', $result->value);
        $players = $match->ludo_player_ids ?? [];
        $winnerId = $players[$winnerColor] ?? null;

        return $winnerId === null ? null : (int) $winnerId;
    }

    /**
     * @param  list<array<string, int|string>>  $path
     * @return array{board: array<string, mixed>, captures: list<array<string, int>>, became_king: bool, result: ?string, extra_turn?: bool}
     */
    private function applyLudoAction(GameMatch $match, string $color, array $path): array
    {
        $action = (string) ($path[0]['action'] ?? '');
        if ($action === 'roll') {
            $board = $this->ludo->rollDice($match->board_state ?? [], $color);

            return [
                'board' => $board,
                'captures' => [],
                'became_king' => false,
                'result' => null,
                'extra_turn' => ! (bool) ($board['skip_turn'] ?? false),
            ];
        }

        if ($action !== 'move') {
            throw new \InvalidArgumentException('Action Ludo invalide.');
        }

        $result = $this->ludo->applyMove($match->board_state ?? [], $color, (int) ($path[0]['piece'] ?? -1));

        return [
            'board' => $result['board'],
            'captures' => [],
            'became_king' => false,
            'result' => $result['result'],
            'extra_turn' => $result['extra_turn'],
        ];
    }

    private function playSoloAiTurns(GameMatch $match): void
    {
        if ($match->game_type === GameType::Damier) {
            $this->playSoloCheckersTurn($match);

            return;
        }

        $this->playSoloLudoTurns($match);
    }

    private function playSoloCheckersTurn(GameMatch $match): void
    {
        $match = GameMatch::query()->lockForUpdate()->findOrFail($match->id);
        if ($match->status !== MatchStatus::InProgress || $match->turn_color !== PieceColor::Black || $match->blackPlayer === null) {
            return;
        }

        $difficulty = 'medium';
        $path = $this->checkersAi->chooseMove($match->board_state ?? [], PieceColor::Black, $difficulty);
        if ($path === []) {
            $this->finish($match, MatchResult::WhiteWin, 'no_legal_move');

            return;
        }

        $applied = $this->engine->applyMove($match->board_state ?? [], PieceColor::Black, $path);
        $seq = $match->move_count + 1;
        GameMove::query()->create([
            'match_id' => $match->id,
            'server_seq' => $seq,
            'player_id' => $match->black_player_id,
            'color' => PieceColor::Black->value,
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
            'turn_color' => PieceColor::White,
            'turn_started_at' => now(),
        ]);

        if ($applied['result'] !== null) {
            $this->finish($match->fresh(['whitePlayer', 'blackPlayer']), MatchResult::from($applied['result']), 'normal');
        } else {
            event(new JbMatchUpdated($match->fresh(['whitePlayer', 'blackPlayer']), 'AiMovePlayed', ['server_seq' => $seq]));
        }
    }

    private function playSoloLudoTurns(GameMatch $match): void
    {
        for ($i = 0; $i < 24; $i++) {
            $match = GameMatch::query()->lockForUpdate()->findOrFail($match->id);
            if ($match->status !== MatchStatus::InProgress) {
                return;
            }

            $board = $match->board_state ?? [];
            $turn = (string) ($board['turn'] ?? 'red');
            if ($turn === 'red') {
                return;
            }

            $difficulty = (string) (($board['_ai']['difficulty'] ?? 'medium'));
            $board = $this->ludo->rollDice($board, $turn);
            $piece = $this->ludoAi->choosePiece($board, $turn, $difficulty);

            if ($piece === null) {
                $seq = $match->move_count + 1;
                $match->update(['board_state' => $board, 'move_count' => $seq, 'turn_started_at' => now()]);
                $this->recordLudoAiMove($match, $seq, $turn, [['action' => 'roll']]);
                continue;
            }

            $result = $this->ludo->applyMove($board, $turn, $piece);
            $seq = $match->move_count + 1;
            $match->update([
                'board_state' => $result['board'],
                'move_count' => $seq,
                'turn_started_at' => now(),
            ]);
            $this->recordLudoAiMove($match, $seq, $turn, [['action' => 'roll'], ['action' => 'move', 'piece' => $piece]]);

            if ($result['result'] !== null) {
                $this->finish($match->fresh(['whitePlayer', 'blackPlayer']), MatchResult::from($result['result']), 'normal');

                return;
            }
        }
    }

    /**
     * @param  list<array<string, int|string>>  $path
     */
    private function recordLudoAiMove(GameMatch $match, int $seq, string $color, array $path): void
    {
        $playerId = ($match->ludo_player_ids ?? [])[$color] ?? null;
        if ($playerId === null) {
            return;
        }

        GameMove::query()->create([
            'match_id' => $match->id,
            'server_seq' => $seq,
            'player_id' => $playerId,
            'color' => $color,
            'path' => $path,
            'captures' => [],
            'became_king' => false,
            'board_after' => $match->board_state,
            'played_at' => now(),
            'created_at' => now(),
        ]);
        event(new JbMatchUpdated($match->fresh(['whitePlayer', 'blackPlayer']), 'AiMovePlayed', ['server_seq' => $seq]));
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

        $isParticipant = $match->game_type === GameType::Ludo
            ? $match->ludoColor($player) !== null
            : $match->playerColor($player) !== null;

        if (! $isParticipant) {
            throw ValidationException::withMessages([
                'player' => ['Vous n\'êtes pas dans cette partie.'],
            ]);
        }
    }

    private function consumeClock(GameMatch $match): void
    {
        if ($match->game_type === GameType::Ludo) {
            return;
        }

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
