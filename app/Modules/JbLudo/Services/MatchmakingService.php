<?php

namespace App\Modules\JbLudo\Services;

use App\Modules\JbLudo\Enums\GameMode;
use App\Modules\JbLudo\Enums\GameType;
use App\Modules\JbLudo\Enums\InviteStatus;
use App\Modules\JbLudo\Models\FriendInvite;
use App\Modules\JbLudo\Models\GameMatch;
use App\Modules\JbLudo\Models\MatchmakingQueueEntry;
use App\Modules\JbLudo\Models\PlayerProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MatchmakingService
{
    public function __construct(
        private readonly MatchLifecycleService $lifecycle,
    ) {}

    public function invite(PlayerProfile $from, PlayerProfile $to): FriendInvite
    {
        if ((int) $from->id === (int) $to->id) {
            throw ValidationException::withMessages([
                'to_player_id' => ['Impossible de s\'inviter soi-même.'],
            ]);
        }

        if ($to->is_suspended) {
            throw ValidationException::withMessages([
                'to_player_id' => ['Ce joueur est suspendu.'],
            ]);
        }

        return FriendInvite::query()->create([
            'from_player_id' => $from->id,
            'to_player_id' => $to->id,
            'status' => InviteStatus::Pending,
        ])->fresh(['fromPlayer', 'toPlayer']);
    }

    public function acceptInvite(FriendInvite $invite, PlayerProfile $accepter): GameMatch
    {
        if ((int) $invite->to_player_id !== (int) $accepter->id) {
            throw ValidationException::withMessages([
                'invite' => ['Cette invitation ne vous est pas destinée.'],
            ]);
        }

        if ($invite->status !== InviteStatus::Pending) {
            throw ValidationException::withMessages([
                'invite' => ['Invitation déjà traitée.'],
            ]);
        }

        return DB::transaction(function () use ($invite): GameMatch {
            $from = $invite->fromPlayer;
            $to = $invite->toPlayer;

            // Tirage simple : invitant = blancs
            $match = $this->lifecycle->createMatch($from, $to, GameMode::Friendly, GameType::Damier);
            $invite->update([
                'status' => InviteStatus::Accepted,
                'match_id' => $match->id,
            ]);

            return $match;
        });
    }

    public function declineInvite(FriendInvite $invite, PlayerProfile $player): FriendInvite
    {
        if ((int) $invite->to_player_id !== (int) $player->id) {
            throw ValidationException::withMessages([
                'invite' => ['Cette invitation ne vous est pas destinée.'],
            ]);
        }

        $invite->update(['status' => InviteStatus::Declined]);

        return $invite->fresh();
    }

    /**
     * @return array{match: ?GameMatch, queued: bool}
     */
    public function enqueueQuick(PlayerProfile $player, GameType $gameType = GameType::Damier): array
    {
        return DB::transaction(function () use ($player, $gameType): array {
            MatchmakingQueueEntry::query()
                ->where('player_id', $player->id)
                ->where('game_type', $gameType)
                ->delete();

            $opponentEntry = MatchmakingQueueEntry::query()
                ->with('player')
                ->where('game_type', $gameType)
                ->where('player_id', '!=', $player->id)
                ->orderByRaw('ABS(points - ?) asc', [$player->points])
                ->orderBy('queued_at')
                ->lockForUpdate()
                ->first();

            if ($gameType === GameType::Ludo) {
                $entries = MatchmakingQueueEntry::query()
                    ->with('player')
                    ->where('game_type', $gameType)
                    ->where('player_id', '!=', $player->id)
                    ->orderBy('queued_at')
                    ->lockForUpdate()
                    ->limit(3)
                    ->get()
                    ->filter(fn (MatchmakingQueueEntry $entry) => $entry->player !== null)
                    ->values();

                if ($entries->count() === 3) {
                    MatchmakingQueueEntry::query()
                        ->whereIn('id', $entries->pluck('id')->all())
                        ->delete();

                    $players = collect([$player])
                        ->merge($entries->map(fn (MatchmakingQueueEntry $entry) => $entry->player))
                        ->values()
                        ->all();

                    return [
                        'match' => $this->lifecycle->createLudoMatch($players, GameMode::Quick),
                        'queued' => false,
                    ];
                }

                MatchmakingQueueEntry::query()->create([
                    'player_id' => $player->id,
                    'game_type' => $gameType,
                    'level' => $player->level->value,
                    'points' => $player->points,
                    'queued_at' => now(),
                ]);

                return ['match' => null, 'queued' => true];
            }

            if ($opponentEntry !== null && $opponentEntry->player !== null) {
                $opponent = $opponentEntry->player;
                $opponentEntry->delete();

                // Plus de points = blancs (léger avantage symbolique)
                if ($player->points >= $opponent->points) {
                    $match = $this->lifecycle->createMatch($player, $opponent, GameMode::Quick, $gameType);
                } else {
                    $match = $this->lifecycle->createMatch($opponent, $player, GameMode::Quick, $gameType);
                }

                return ['match' => $match, 'queued' => false];
            }

            MatchmakingQueueEntry::query()->create([
                'player_id' => $player->id,
                'game_type' => $gameType,
                'level' => $player->level->value,
                'points' => $player->points,
                'queued_at' => now(),
            ]);

            return ['match' => null, 'queued' => true];
        });
    }

    public function leaveQueue(PlayerProfile $player): void
    {
        MatchmakingQueueEntry::query()->where('player_id', $player->id)->delete();
    }
}
