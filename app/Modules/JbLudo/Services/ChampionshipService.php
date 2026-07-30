<?php

namespace App\Modules\JbLudo\Services;

use App\Modules\JbLudo\Enums\ChampionshipParticipantStatus;
use App\Modules\JbLudo\Enums\ChampionshipStatus;
use App\Modules\JbLudo\Enums\GameMode;
use App\Modules\JbLudo\Enums\MatchStatus;
use App\Modules\JbLudo\Enums\PieceColor;
use App\Modules\JbLudo\Models\Championship;
use App\Modules\JbLudo\Models\ChampionshipParticipant;
use App\Modules\JbLudo\Models\GameMatch;
use App\Modules\JbLudo\Models\PlayerProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ChampionshipService
{
    public function __construct(
        private readonly CheckersEngineService $engine,
    ) {}

    /**
     * @return array{added: int, skipped: int}
     */
    public function addEligiblePlayers(Championship $championship): array
    {
        if ($championship->bracket_generated_at !== null) {
            throw ValidationException::withMessages([
                'championship' => ['La repartition est deja generee.'],
            ]);
        }

        return DB::transaction(function () use ($championship): array {
            /** @var Championship $championship */
            $championship = Championship::query()->lockForUpdate()->findOrFail($championship->id);
            $already = $championship->participants()->count();
            $remaining = max(0, (int) $championship->max_participants - $already);

            if ($remaining === 0) {
                return ['added' => 0, 'skipped' => 0];
            }

            $existingIds = $championship->participants()->pluck('player_profile_id')->all();
            $players = PlayerProfile::query()
                ->where('is_suspended', false)
                ->whereNotIn('id', $existingIds)
                ->orderByDesc('points')
                ->orderBy('id')
                ->limit($remaining)
                ->get();

            foreach ($players as $player) {
                ChampionshipParticipant::query()->create([
                    'championship_id' => $championship->id,
                    'player_profile_id' => $player->id,
                    'status' => ChampionshipParticipantStatus::Registered,
                ]);
            }

            $championship->update([
                'participants_count' => $championship->participants()->count(),
                'status' => ChampionshipStatus::RegistrationOpen,
            ]);

            return ['added' => $players->count(), 'skipped' => max(0, $remaining - $players->count())];
        });
    }

    /**
     * @return array{participants: int, bracket_size: int, byes: int, matches: int, rounds: int}
     */
    public function generateFirstRound(Championship $championship, bool $randomize = false): array
    {
        return DB::transaction(function () use ($championship, $randomize): array {
            /** @var Championship $championship */
            $championship = Championship::query()->lockForUpdate()->findOrFail($championship->id);

            if ($championship->bracket_generated_at !== null) {
                throw ValidationException::withMessages([
                    'championship' => ['La repartition de ce championnat existe deja.'],
                ]);
            }

            /** @var Collection<int, ChampionshipParticipant> $participants */
            $participants = $championship->participants()
                ->with('player')
                ->where('status', ChampionshipParticipantStatus::Registered)
                ->get()
                ->filter(fn (ChampionshipParticipant $p) => $p->player !== null && ! $p->player->is_suspended)
                ->values();

            if ($participants->count() < 2) {
                throw ValidationException::withMessages([
                    'participants' => ['Il faut au moins deux joueurs actifs pour generer un championnat.'],
                ]);
            }

            $participants = $randomize
                ? $participants->shuffle()->values()
                : $participants->sort(function (ChampionshipParticipant $a, ChampionshipParticipant $b): int {
                    $points = ($b->player->points <=> $a->player->points);

                    return $points !== 0 ? $points : ($a->player->id <=> $b->player->id);
                })->values();

            $participantCount = $participants->count();
            $bracketSize = $this->nextPowerOfTwo($participantCount);
            $byeCount = $bracketSize - $participantCount;
            $rounds = (int) log($bracketSize, 2);

            foreach ($participants as $index => $participant) {
                $participant->update(['seed_number' => $index + 1]);
            }

            $byeParticipants = $participants->take($byeCount);
            foreach ($byeParticipants as $participant) {
                $participant->update([
                    'status' => ChampionshipParticipantStatus::Bye,
                    'bye_count' => $participant->bye_count + 1,
                ]);
            }

            $matchParticipants = $participants->slice($byeCount)->values();
            $matchesCreated = 0;
            for ($i = 0; $i < $matchParticipants->count(); $i += 2) {
                $white = $matchParticipants[$i]->player;
                $black = $matchParticipants[$i + 1]->player;

                GameMatch::query()->create([
                    'reference' => 'JBC-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                    'mode' => GameMode::Championship,
                    'championship_id' => $championship->id,
                    'championship_round' => 1,
                    'championship_match_number' => $matchesCreated + 1,
                    'status' => MatchStatus::InProgress,
                    'white_player_id' => $white->id,
                    'black_player_id' => $black->id,
                    'turn_color' => PieceColor::White,
                    'board_state' => $this->engine->initialBoard(),
                    'clock_seconds' => (int) config('mami.jb_ludo.default_clock_seconds', 600),
                    'white_time_left' => (int) config('mami.jb_ludo.default_clock_seconds', 600),
                    'black_time_left' => (int) config('mami.jb_ludo.default_clock_seconds', 600),
                    'turn_started_at' => now(),
                    'started_at' => now(),
                ]);

                $matchParticipants[$i]->update(['status' => ChampionshipParticipantStatus::Active]);
                $matchParticipants[$i + 1]->update(['status' => ChampionshipParticipantStatus::Active]);
                $matchesCreated++;
            }

            $championship->update([
                'status' => ChampionshipStatus::InProgress,
                'participants_count' => $participantCount,
                'rounds_count' => $rounds,
                'current_round' => 1,
                'bracket_generated_at' => now(),
            ]);

            return [
                'participants' => $participantCount,
                'bracket_size' => $bracketSize,
                'byes' => $byeCount,
                'matches' => $matchesCreated,
                'rounds' => $rounds,
            ];
        });
    }

    /**
     * @return array{round: int, advancers: int, matches: int, finished: bool, champion: ?string}
     */
    public function generateNextRound(Championship $championship): array
    {
        return DB::transaction(function () use ($championship): array {
            /** @var Championship $championship */
            $championship = Championship::query()->lockForUpdate()->findOrFail($championship->id);

            if ($championship->bracket_generated_at === null) {
                throw ValidationException::withMessages([
                    'championship' => ['Generez d\'abord le premier tour.'],
                ]);
            }

            if ($championship->status === ChampionshipStatus::Finished) {
                throw ValidationException::withMessages([
                    'championship' => ['Ce championnat est deja termine.'],
                ]);
            }

            $currentRound = (int) $championship->current_round;
            $existingNextRound = GameMatch::query()
                ->where('championship_id', $championship->id)
                ->where('championship_round', $currentRound + 1)
                ->exists();

            if ($existingNextRound) {
                throw ValidationException::withMessages([
                    'round' => ['Le tour suivant existe deja.'],
                ]);
            }

            $roundMatches = GameMatch::query()
                ->where('championship_id', $championship->id)
                ->where('championship_round', $currentRound)
                ->orderBy('championship_match_number')
                ->get();

            if ($roundMatches->isEmpty()) {
                throw ValidationException::withMessages([
                    'round' => ['Aucune partie trouvee pour le tour courant.'],
                ]);
            }

            $unfinished = $roundMatches
                ->filter(fn (GameMatch $match) => $match->status !== MatchStatus::Finished)
                ->count();

            if ($unfinished > 0) {
                throw ValidationException::withMessages([
                    'round' => ["$unfinished partie(s) du tour courant ne sont pas terminee(s)."],
                ]);
            }

            $winnerIds = [];
            foreach ($roundMatches as $match) {
                if ($match->winner_id === null) {
                    throw ValidationException::withMessages([
                        'winner' => ['Une partie terminee sans vainqueur doit etre rejouee ou tranchee avant le tour suivant.'],
                    ]);
                }

                $winnerIds[] = (int) $match->winner_id;
                $loserId = (int) $match->winner_id === (int) $match->white_player_id
                    ? $match->black_player_id
                    : $match->white_player_id;

                if ($loserId !== null) {
                    ChampionshipParticipant::query()
                        ->where('championship_id', $championship->id)
                        ->where('player_profile_id', $loserId)
                        ->update([
                            'status' => ChampionshipParticipantStatus::Eliminated,
                            'eliminated_at' => now(),
                        ]);
                }
            }

            $advancerIds = collect($winnerIds);
            if ($currentRound === 1) {
                $byeIds = ChampionshipParticipant::query()
                    ->where('championship_id', $championship->id)
                    ->where('status', ChampionshipParticipantStatus::Bye)
                    ->pluck('player_profile_id');
                $advancerIds = $byeIds->merge($advancerIds);
            }

            $advancerIds = $advancerIds->unique()->values();
            $advancers = ChampionshipParticipant::query()
                ->with('player')
                ->where('championship_id', $championship->id)
                ->whereIn('player_profile_id', $advancerIds->all())
                ->orderBy('seed_number')
                ->get()
                ->filter(fn (ChampionshipParticipant $participant) => $participant->player !== null)
                ->values();

            if ($advancers->count() === 1) {
                $champion = $advancers->first();
                $champion->update(['status' => ChampionshipParticipantStatus::Champion]);
                $championship->update([
                    'status' => ChampionshipStatus::Finished,
                    'champion_player_id' => $champion->player_profile_id,
                ]);

                return [
                    'round' => $currentRound,
                    'advancers' => 1,
                    'matches' => 0,
                    'finished' => true,
                    'champion' => $champion->player?->pseudo,
                ];
            }

            if ($advancers->count() % 2 !== 0) {
                throw ValidationException::withMessages([
                    'advancers' => ['Nombre de qualifies impair. Verifiez la repartition du championnat.'],
                ]);
            }

            $nextRound = $currentRound + 1;
            $matchesCreated = 0;
            for ($i = 0; $i < $advancers->count(); $i += 2) {
                $white = $advancers[$i]->player;
                $black = $advancers[$i + 1]->player;

                GameMatch::query()->create([
                    'reference' => 'JBC-'.now()->format('Ymd').'-'.Str::upper(Str::random(6)),
                    'mode' => GameMode::Championship,
                    'championship_id' => $championship->id,
                    'championship_round' => $nextRound,
                    'championship_match_number' => $matchesCreated + 1,
                    'status' => MatchStatus::InProgress,
                    'white_player_id' => $white->id,
                    'black_player_id' => $black->id,
                    'turn_color' => PieceColor::White,
                    'board_state' => $this->engine->initialBoard(),
                    'clock_seconds' => (int) config('mami.jb_ludo.default_clock_seconds', 600),
                    'white_time_left' => (int) config('mami.jb_ludo.default_clock_seconds', 600),
                    'black_time_left' => (int) config('mami.jb_ludo.default_clock_seconds', 600),
                    'turn_started_at' => now(),
                    'started_at' => now(),
                ]);

                $advancers[$i]->update(['status' => ChampionshipParticipantStatus::Active]);
                $advancers[$i + 1]->update(['status' => ChampionshipParticipantStatus::Active]);
                $matchesCreated++;
            }

            $championship->update([
                'status' => ChampionshipStatus::InProgress,
                'current_round' => $nextRound,
            ]);

            return [
                'round' => $nextRound,
                'advancers' => $advancers->count(),
                'matches' => $matchesCreated,
                'finished' => false,
                'champion' => null,
            ];
        });
    }

    private function nextPowerOfTwo(int $value): int
    {
        $power = 1;
        while ($power < $value) {
            $power *= 2;
        }

        return $power;
    }
}
