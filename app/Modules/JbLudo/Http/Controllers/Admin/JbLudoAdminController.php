<?php

namespace App\Modules\JbLudo\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\JbLudo\Enums\MatchStatus;
use App\Modules\JbLudo\Models\GameMatch;
use App\Modules\JbLudo\Models\PlayerProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class JbLudoAdminController extends Controller
{
    public function dashboard(Request $request): View
    {
        $threshold = (int) config('mami.jb_ludo.repeated_resign_threshold', 5);
        $lookup = trim((string) $request->query('lookup', ''));

        $passwordLookupResults = collect();
        if ($lookup !== '') {
            $passwordLookupResults = PlayerProfile::query()
                ->with('user:id,name,email')
                ->where(function ($query) use ($lookup): void {
                    $query->where('pseudo', 'like', '%'.$lookup.'%')
                        ->orWhere('phone', 'like', '%'.$lookup.'%')
                        ->orWhereHas('user', function ($userQuery) use ($lookup): void {
                            $userQuery->where('email', 'like', '%'.$lookup.'%')
                                ->orWhere('name', 'like', '%'.$lookup.'%');
                        });
                })
                ->orderBy('pseudo')
                ->limit(10)
                ->get();
        }

        return view('admin.jb-ludo.dashboard', [
            'playersCount' => PlayerProfile::query()->count(),
            'onlineCount' => PlayerProfile::query()->where('is_online', true)->count(),
            'matchesToday' => GameMatch::query()->whereDate('created_at', today())->count(),
            'liveMatches' => GameMatch::query()
                ->whereIn('status', [MatchStatus::InProgress, MatchStatus::Grace])
                ->count(),
            'frequentResigners' => PlayerProfile::query()
                ->where('resign_count', '>=', $threshold)
                ->orderByDesc('resign_count')
                ->limit(10)
                ->get(),
            'lookup' => $lookup,
            'passwordLookupResults' => $passwordLookupResults,
        ]);
    }

    public function players(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $players = PlayerProfile::query()
            ->with('user:id,name,email')
            ->when($q !== '', fn ($query) => $query->where(function ($inner) use ($q): void {
                $inner->where('pseudo', 'like', '%'.$q.'%')
                    ->orWhere('phone', 'like', '%'.$q.'%')
                    ->orWhere('city', 'like', '%'.$q.'%');
            }))
            ->orderByDesc('points')
            ->paginate(30)
            ->withQueryString();

        return view('admin.jb-ludo.players', [
            'players' => $players,
            'filters' => ['q' => $q],
        ]);
    }

    public function toggleSuspend(Request $request, PlayerProfile $player): RedirectResponse
    {
        if ($player->is_suspended) {
            $player->update([
                'is_suspended' => false,
                'suspended_at' => null,
                'suspension_reason' => null,
            ]);
            $message = 'Joueur réactivé.';
        } else {
            $data = $request->validate([
                'reason' => ['nullable', 'string', 'max:255'],
            ]);
            $player->update([
                'is_suspended' => true,
                'suspended_at' => now(),
                'suspension_reason' => $data['reason'] ?? 'Suspension administrative',
                'is_online' => false,
            ]);
            $message = 'Joueur suspendu.';
        }

        return back()->with('success', $message);
    }

    public function resetPassword(PlayerProfile $player): RedirectResponse
    {
        $user = $player->user;
        if ($user === null) {
            return back()->withErrors(['password' => 'Aucun compte utilisateur lié à ce profil joueur.']);
        }

        $temporaryPassword = Str::password(10, symbols: false);

        $user->forceFill([
            'password' => $temporaryPassword,
        ])->save();

        return back()
            ->with('success', 'Mot de passe réinitialisé pour '.$player->pseudo.'.')
            ->with('jb_temp_password', [
                'pseudo' => $player->pseudo,
                'email' => $user->email,
                'password' => $temporaryPassword,
            ]);
    }

    public function matches(Request $request): View
    {
        $status = $request->query('status');

        $matches = GameMatch::query()
            ->with(['whitePlayer', 'blackPlayer', 'winner'])
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        return view('admin.jb-ludo.matches', [
            'matches' => $matches,
            'filters' => ['status' => $status],
            'statuses' => MatchStatus::cases(),
        ]);
    }

    public function showMatch(GameMatch $match): View
    {
        $match->load(['whitePlayer', 'blackPlayer', 'winner', 'moves.player']);

        return view('admin.jb-ludo.match-show', [
            'match' => $match,
        ]);
    }

    public function leaderboard(): View
    {
        $players = PlayerProfile::query()
            ->where('is_suspended', false)
            ->orderByDesc('points')
            ->orderBy('id')
            ->paginate(50);

        return view('admin.jb-ludo.leaderboard', [
            'players' => $players,
        ]);
    }
}
