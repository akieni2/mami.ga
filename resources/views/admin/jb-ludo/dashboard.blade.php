@extends('layouts.admin')

@section('title', 'JB Games')
@section('page_title', 'JB Games')
@section('page_subtitle', 'Tableau de bord Ludo & Damier')

@section('content')
    @include('admin.jb-ludo.partials.nav', ['active' => 'dashboard'])

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

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-xs uppercase text-slate-500">Joueurs</p>
            <p class="mt-1 text-2xl font-semibold">{{ $playersCount }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-xs uppercase text-slate-500">En ligne</p>
            <p class="mt-1 text-2xl font-semibold">{{ $onlineCount }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-xs uppercase text-slate-500">Parties aujourd'hui</p>
            <p class="mt-1 text-2xl font-semibold">{{ $matchesToday }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4">
            <p class="text-xs uppercase text-slate-500">Parties en cours</p>
            <p class="mt-1 text-2xl font-semibold">{{ $liveMatches }}</p>
        </div>
    </div>

    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="mb-1 text-sm font-semibold text-slate-800">Réinitialiser un mot de passe</h2>
        <p class="mb-3 text-sm text-slate-500">Recherchez un joueur par pseudo, email, nom ou téléphone.</p>

        <form method="GET" action="{{ route('admin.jb-ludo.dashboard') }}" class="mb-4 flex flex-wrap gap-2">
            <input type="search" name="lookup" value="{{ $lookup ?? '' }}"
                   placeholder="ex. jean@mail.com ou Pseudo"
                   class="min-w-[16rem] flex-1 rounded-lg border-slate-200 text-sm">
            <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm text-white">Rechercher</button>
        </form>

        @if (($lookup ?? '') !== '')
            <div class="overflow-hidden rounded-lg border border-slate-100">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-3 py-2">Pseudo</th>
                        <th class="px-3 py-2">Email</th>
                        <th class="px-3 py-2">Téléphone</th>
                        <th class="px-3 py-2"></th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse ($passwordLookupResults as $player)
                        <tr>
                            <td class="px-3 py-2 font-medium">{{ $player->pseudo }}</td>
                            <td class="px-3 py-2">{{ $player->user?->email ?? '—' }}</td>
                            <td class="px-3 py-2">{{ $player->phone ?? '—' }}</td>
                            <td class="px-3 py-2 text-right">
                                <form method="POST" action="{{ route('admin.jb-ludo.players.reset-password', $player) }}"
                                      onsubmit="return confirm('Réinitialiser le mot de passe de {{ $player->pseudo }} ?');">
                                    @csrf
                                    <button class="text-sky-700 hover:underline">Réinitialiser</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-6 text-center text-slate-500">Aucun joueur trouvé.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="mb-3 text-sm font-semibold text-slate-800">Abandons répétés</h2>
        @forelse ($frequentResigners as $player)
            <div class="flex items-center justify-between border-b border-slate-100 py-2 text-sm last:border-0">
                <span>{{ $player->pseudo }} ({{ $player->city }})</span>
                <span class="font-mono text-rose-600">{{ $player->resign_count }} abandons</span>
            </div>
        @empty
            <p class="text-sm text-slate-500">Aucun signalement.</p>
        @endforelse
    </div>
@endsection
