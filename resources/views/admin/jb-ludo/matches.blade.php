@extends('layouts.admin')

@section('title', 'JB Ludo — Parties')
@section('page_title', 'JB Ludo')
@section('page_subtitle', 'Parties')

@section('content')
    @include('admin.jb-ludo.partials.nav', ['active' => 'matches'])

    <form method="GET" class="mb-4 flex gap-2">
        <select name="status" class="rounded-lg border-slate-200 text-sm">
            <option value="">Tous les statuts</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->value }}</option>
            @endforeach
        </select>
        <button class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Filtrer</button>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
            <tr>
                <th class="px-4 py-3">Réf.</th>
                <th class="px-4 py-3">Mode</th>
                <th class="px-4 py-3">Blancs</th>
                <th class="px-4 py-3">Noirs</th>
                <th class="px-4 py-3">Statut</th>
                <th class="px-4 py-3"></th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            @forelse ($matches as $match)
                <tr>
                    <td class="px-4 py-3 font-mono text-xs">{{ $match->reference }}</td>
                    <td class="px-4 py-3">{{ $match->mode->value }}</td>
                    <td class="px-4 py-3">{{ $match->whitePlayer?->pseudo ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $match->blackPlayer?->pseudo ?? '—' }}</td>
                    <td class="px-4 py-3">{{ $match->status->value }}</td>
                    <td class="px-4 py-3 text-right">
                        <a href="{{ route('admin.jb-ludo.matches.show', $match) }}" class="text-sky-600 hover:underline">Détail</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Aucune partie.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="border-t border-slate-100 px-4 py-3">{{ $matches->links() }}</div>
    </div>
@endsection
