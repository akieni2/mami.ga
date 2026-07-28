@extends('layouts.admin')

@section('title', 'JB Ludo — Joueurs')
@section('page_title', 'JB Ludo')
@section('page_subtitle', 'Joueurs inscrits')

@section('content')
    @include('admin.jb-ludo.partials.nav', ['active' => 'players'])

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
                    <td class="px-4 py-3 text-right">
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
