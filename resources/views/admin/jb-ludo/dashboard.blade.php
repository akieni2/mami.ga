@extends('layouts.admin')

@section('title', 'JB Ludo')
@section('page_title', 'JB Ludo')
@section('page_subtitle', 'Tableau de bord jeu de dames')

@section('content')
    @include('admin.jb-ludo.partials.nav', ['active' => 'dashboard'])

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
