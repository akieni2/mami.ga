@extends('layouts.admin')

@section('title', $championship->name)
@section('page_title', $championship->name)
@section('page_subtitle', 'Participants, repartition et parties du championnat')

@section('content')
    @include('admin.jb-ludo.partials.nav', ['active' => 'championships'])

    <div class="mb-5 grid gap-4 xl:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-sm text-slate-500">Jeu</div>
            <div class="mt-1 text-xl font-semibold">{{ ucfirst($championship->game_type->value) }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-sm text-slate-500">Niveau</div>
            <div class="mt-1 text-xl font-semibold">{{ $championship->scope->value }}</div>
            <div class="mt-1 text-sm text-slate-500">{{ collect([$championship->country, $championship->city, $championship->neighborhood])->filter()->implode(' · ') }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-sm text-slate-500">Prix</div>
            <div class="mt-1 text-xl font-semibold">{{ $championship->prize_title ?? '—' }}</div>
            @if($championship->prize_amount)
                <div class="mt-1 text-sm text-slate-500">{{ number_format($championship->prize_amount, 0, ',', ' ') }} {{ $championship->prize_currency }}</div>
            @endif
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-sm text-slate-500">Statut</div>
            <div class="mt-1 text-xl font-semibold">{{ $championship->status->value }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-sm text-slate-500">Participants</div>
            <div class="mt-1 text-xl font-semibold">{{ $championship->participants_count }} / {{ $championship->max_participants }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-sm text-slate-500">Tours</div>
            <div class="mt-1 text-xl font-semibold">{{ $championship->rounds_count ?: '—' }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="text-sm text-slate-500">Parties creees</div>
            <div class="mt-1 text-xl font-semibold">{{ $championship->matches()->count() }}</div>
        </div>
    </div>

    @if ($championship->bracket_generated_at === null)
        <div class="mb-5 flex flex-wrap gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <form method="POST" action="{{ route('admin.jb-ludo.championships.add-eligible', $championship) }}">
                @csrf
                <button type="submit" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm hover:bg-slate-50">
                    Ajouter les joueurs actifs
                </button>
            </form>

            <form method="POST" action="{{ route('admin.jb-ludo.championships.generate', $championship) }}" class="flex flex-wrap gap-2">
                @csrf
                <select name="distribution" class="rounded-lg border-slate-200 text-sm">
                    <option value="ranking">Repartition par classement</option>
                    <option value="random">Tirage aleatoire</option>
                </select>
                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                    Generer le 1er tour
                </button>
            </form>
        </div>
    @endif


    @if ($championship->bracket_generated_at !== null && $championship->status->value !== 'finished')
        <div class="mb-5 rounded-xl border border-amber-200 bg-amber-50 p-4 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="font-semibold text-amber-950">Progression du championnat</h2>
                    <p class="mt-1 text-sm text-amber-800">Quand toutes les parties du tour courant sont terminees, le systeme cree automatiquement le tour suivant.</p>
                </div>
                <form method="POST" action="{{ route('admin.jb-ludo.championships.next-round', $championship) }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-amber-900 px-4 py-2 text-sm font-medium text-white hover:bg-amber-800">
                        Generer le tour suivant
                    </button>
                </form>
            </div>
        </div>
    @endif
    <div class="grid gap-5 xl:grid-cols-2">
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold">Participants</h2>
            </div>
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Seed</th>
                        <th class="px-5 py-3">Joueur</th>
                        <th class="px-5 py-3">Points</th>
                        <th class="px-5 py-3">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($participants as $participant)
                        <tr>
                            <td class="px-5 py-3">{{ $participant->seed_number ?? '—' }}</td>
                            <td class="px-5 py-3 font-medium">{{ $participant->player?->pseudo ?? '—' }}</td>
                            <td class="px-5 py-3">{{ $participant->player?->points ?? 0 }}</td>
                            <td class="px-5 py-3">{{ $participant->status->value }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-10 text-center text-slate-500">Aucun participant.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if ($participants->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">{{ $participants->links() }}</div>
            @endif
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold">Parties du championnat</h2>
            </div>
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Tour</th>
                        <th class="px-5 py-3">Match</th>
                        <th class="px-5 py-3">Blancs</th>
                        <th class="px-5 py-3">Noirs</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($matches as $match)
                        <tr>
                            <td class="px-5 py-3">{{ $match->championship_round }}</td>
                            <td class="px-5 py-3">{{ $match->championship_match_number }}</td>
                            <td class="px-5 py-3">{{ $match->whitePlayer?->pseudo ?? '—' }}</td>
                            <td class="px-5 py-3">{{ $match->blackPlayer?->pseudo ?? '—' }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('admin.jb-ludo.matches.show', $match) }}" class="text-sky-600 hover:underline">Voir</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-10 text-center text-slate-500">Aucune partie generee.</td></tr>
                    @endforelse
                </tbody>
            </table>
            @if ($matches->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">{{ $matches->links() }}</div>
            @endif
        </section>
    </div>
@endsection
