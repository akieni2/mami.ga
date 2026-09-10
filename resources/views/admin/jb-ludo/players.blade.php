@extends('layouts.admin')

@section('title', 'JB Ludo — Joueurs')
@section('page_title', 'JB Ludo')
@section('page_subtitle', 'Joueurs inscrits')

@section('content')
    @include('admin.jb-ludo.partials.nav', ['active' => 'players'])

    @if (session('jb_temp_password'))
        @php($tmp = session('jb_temp_password'))
        <div class="mb-6 rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-sm text-emerald-950">
            <p class="font-semibold">Mot de passe temporaire (à communiquer une seule fois)</p>
            <dl class="mt-2 grid gap-1 sm:grid-cols-3">
                <div><span class="text-emerald-700">Pseudo :</span> <strong>{{ $tmp['pseudo'] }}</strong></div>
                <div><span class="text-emerald-700">Email :</span> <strong>{{ $tmp['email'] }}</strong></div>
                <div><span class="text-emerald-700">Mot de passe :</span> <strong class="font-mono text-base">{{ $tmp['password'] }}</strong></div>
            </dl>
        </div>
    @endif

    <form method="GET" class="mb-4 flex gap-2">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Pseudo, téléphone, ville"
               class="min-w-[16rem] flex-1 rounded-lg border-slate-200 text-sm">
        <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Rechercher</button>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
            <tr>
                <th class="px-4 py-3">Pseudo</th>
                <th class="px-4 py-3">Ville</th>
                <th class="px-4 py-3">Points</th>
                <th class="px-4 py-3">Parties</th>
                <th class="px-4 py-3">Statut</th>
                <th class="px-4 py-3"></th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($players as $player)
                <tr>
                    <td class="px-4 py-3 font-medium">{{ $player->pseudo }}</td>
                    <td class="px-4 py-3">{{ $player->city ?? '—' }}</td>
                    <td class="px-4 py-3 font-mono">{{ $player->points }}</td>
                    <td class="px-4 py-3">{{ $player->games_played }}</td>
                    <td class="px-4 py-3">
                        @if ($player->is_suspended)
                            <span class="text-rose-600">Suspendu</span>
                        @elseif ($player->is_online)
                            <span class="text-emerald-600">En ligne</span>
                        @else
                            Hors ligne
                        @endif
                    </td>
                    <td class="px-4 py-3 text-right space-x-3">
                        <form method="POST" action="{{ route('admin.jb-ludo.players.reset-password', $player) }}"
                              class="inline"
                              onsubmit="return confirm('Réinitialiser le mot de passe de {{ $player->pseudo }} ?');">
                            @csrf
                            <button class="text-amber-700 hover:underline">Mot de passe</button>
                        </form>
                        <form method="POST" action="{{ route('admin.jb-ludo.players.toggle', $player) }}" class="inline">
                            @csrf
                            @if (! $player->is_suspended)
                                <input type="hidden" name="reason" value="Suspension administrative">
                            @endif
                            <button class="text-sky-600 hover:underline">
                                {{ $player->is_suspended ? 'Réactiver' : 'Suspendre' }}
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Aucun joueur.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="border-t border-slate-100 px-4 py-3">{{ $players->links() }}</div>
    </div>
@endsection
