@extends('layouts.admin')

@section('title', 'Championnats JB Ludo')
@section('page_title', 'Championnats JB Ludo')
@section('page_subtitle', 'Creation et repartition automatique des joueurs')

@section('content')
    @include('admin.jb-ludo.partials.nav', ['active' => 'championships'])

    <div class="mb-4 flex justify-end">
        <a href="{{ route('admin.jb-ludo.championships.create') }}"
           class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
            Creer un championnat
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-5 py-3">Championnat</th>
                    <th class="px-5 py-3">Jeu</th>
                    <th class="px-5 py-3">Niveau</th>
                    <th class="px-5 py-3">Statut</th>
                    <th class="px-5 py-3">Participants</th>
                    <th class="px-5 py-3">Parties</th>
                    <th class="px-5 py-3">Champion</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($championships as $championship)
                    <tr>
                        <td class="px-5 py-3 font-medium text-slate-900">{{ $championship->name }}</td>
                        <td class="px-5 py-3">{{ ucfirst($championship->game_type->value) }}</td>
                        <td class="px-5 py-3">{{ $championship->scope->value }} {{ $championship->city ? '· '.$championship->city : '' }}</td>
                        <td class="px-5 py-3">{{ $championship->status->value }}</td>
                        <td class="px-5 py-3">{{ $championship->participants_count }} / {{ $championship->max_participants }}</td>
                        <td class="px-5 py-3">{{ $championship->matches_count }}</td>
                        <td class="px-5 py-3">{{ $championship->champion?->pseudo ?? '—' }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('admin.jb-ludo.championships.show', $championship) }}" class="text-sky-600 hover:underline">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-5 py-10 text-center text-slate-500">Aucun championnat.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($championships->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $championships->links() }}</div>
        @endif
    </div>
@endsection
