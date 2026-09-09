<?php

namespace App\Modules\JbLudo\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\JbLudo\Enums\ChampionshipStatus;
use App\Modules\JbLudo\Enums\CompetitionScope;
use App\Modules\JbLudo\Enums\GameType;
use App\Modules\JbLudo\Models\Championship;
use App\Modules\JbLudo\Services\ChampionshipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class JbLudoChampionshipAdminController extends Controller
{
    public function index(): View
    {
        $championships = Championship::query()
            ->withCount(['participants', 'matches'])
            ->with('champion')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.jb-ludo.championships.index', compact('championships'));
    }

    public function create(): View
    {
        return view('admin.jb-ludo.championships.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'game_type' => ['required', 'in:damier'],
            'scope' => ['required', 'in:'.implode(',', CompetitionScope::values())],
            'country' => ['nullable', 'string', 'max:80'],
            'city' => ['nullable', 'string', 'max:80'],
            'neighborhood' => ['nullable', 'string', 'max:120'],
            'name' => ['required', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'max_participants' => ['required', 'integer', 'min:2', 'max:5000'],
            'prize_title' => ['nullable', 'string', 'max:160'],
            'prize_amount' => ['nullable', 'integer', 'min:0'],
            'prize_currency' => ['nullable', 'string', 'max:10'],
            'prize_description' => ['nullable', 'string', 'max:2000'],
            'registration_closes_at' => ['nullable', 'date'],
            'starts_at' => ['nullable', 'date'],
        ]);

        $championship = Championship::query()->create([
            'created_by' => $request->user()?->id,
            'game_type' => GameType::from($data['game_type']),
            'scope' => CompetitionScope::from($data['scope']),
            'country' => $data['country'] ?: 'Gabon',
            'city' => $data['city'] ?? null,
            'neighborhood' => $data['neighborhood'] ?? null,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'max_participants' => $data['max_participants'],
            'prize_title' => $data['prize_title'] ?? null,
            'prize_amount' => $data['prize_amount'] ?? null,
            'prize_currency' => $data['prize_currency'] ?? 'XAF',
            'prize_description' => $data['prize_description'] ?? null,
            'registration_closes_at' => $data['registration_closes_at'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'status' => ChampionshipStatus::Draft,
        ]);

        return redirect()
            ->route('admin.jb-ludo.championships.show', $championship)
            ->with('success', 'Championnat cree. Vous pouvez maintenant ajouter les joueurs.');
    }

    public function show(Championship $championship): View
    {
        $championship->load([
            'participants.player.user:id,name,email',
            'matches.whitePlayer',
            'matches.blackPlayer',
            'matches.winner',
            'champion',
        ]);

        $participants = $championship->participants()
            ->with('player')
            ->orderBy('seed_number')
            ->orderBy('id')
            ->paginate(100);

        $matches = $championship->matches()
            ->with(['whitePlayer', 'blackPlayer', 'winner'])
            ->orderBy('championship_round')
            ->orderBy('championship_match_number')
            ->paginate(100, ['*'], 'matches_page');

        return view('admin.jb-ludo.championships.show', compact('championship', 'participants', 'matches'));
    }

    public function addEligiblePlayers(Championship $championship, ChampionshipService $service): RedirectResponse
    {
        $result = $service->addEligiblePlayers($championship);

        return back()->with('success', $result['added'].' joueur(s) ajoute(s) au championnat.');
    }

    public function generate(Request $request, Championship $championship, ChampionshipService $service): RedirectResponse
    {
        $data = $request->validate([
            'distribution' => ['nullable', 'in:ranking,random'],
        ]);

        $result = $service->generateFirstRound(
            $championship,
            ($data['distribution'] ?? 'ranking') === 'random',
        );

        return back()->with('success', sprintf(
            'Repartition generee: %d participant(s), grille %d, %d qualification(s) automatique(s), %d partie(s) creee(s).',
            $result['participants'],
            $result['bracket_size'],
            $result['byes'],
            $result['matches'],
        ));
    }
    public function nextRound(Championship $championship, ChampionshipService $service): RedirectResponse
    {
        $result = $service->generateNextRound($championship);

        if ($result['finished']) {
            return back()->with('success', 'Championnat termine. Champion: '.($result['champion'] ?? 'joueur qualifie'));
        }

        return back()->with('success', sprintf(
            'Tour %d genere: %d qualifie(s), %d nouvelle(s) partie(s).',
            $result['round'],
            $result['advancers'],
            $result['matches'],
        ));
    }
}
