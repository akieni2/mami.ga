@extends('layouts.admin')

@section('title', 'JB Ludo — Classement')
@section('page_title', 'JB Ludo')
@section('page_subtitle', 'Classement général')

@section('content')
    @include('admin.jb-ludo.partials.nav', ['active' => 'leaderboard'])

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
            <tr>
                <th class="px-4 py-3">#</th>
                <th class="px-4 py-3">Pseudo</th>
                <th class="px-4 py-3">Ville</th>
                <th class="px-4 py-3">Points</th>
                <th class="px-4 py-3">V / N / D</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @foreach ($players as $index => $player)
                <tr>
                    <td class="px-4 py-3">{{ $players->firstItem() + $index }}</td>
                    <td class="px-4 py-3 font-medium">{{ $player->pseudo }}</td>
                    <td class="px-4 py-3">{{ $player->city ?? '—' }}</td>
                    <td class="px-4 py-3 font-mono">{{ $player->points }}</td>
                    <td class="px-4 py-3">{{ $player->games_won }} / {{ $player->games_drawn }} / {{ $player->games_lost }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
        <div class="border-t border-slate-100 px-4 py-3">{{ $players->links() }}</div>
    </div>
@endsection
