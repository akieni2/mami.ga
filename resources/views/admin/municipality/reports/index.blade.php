@extends('layouts.admin')

@section('title', 'Signalements citoyens')
@section('page_title', 'Signalements Owendo')
@section('page_subtitle', 'Liste des signalements géolocalisés')
@section('admin_page', 'municipality-reports')

@section('content')
    <form method="GET" class="mb-4 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-5">
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Statut</label>
            <select name="status" class="w-full rounded-lg border-slate-200 text-sm">
                <option value="">Tous</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Catégorie</label>
            <select name="category" class="w-full rounded-lg border-slate-200 text-sm">
                <option value="">Toutes</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->value }}" @selected(($filters['category'] ?? '') === $category->value)>{{ $category->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Quartier</label>
            <select name="sector_id" class="w-full rounded-lg border-slate-200 text-sm">
                <option value="">Tous</option>
                @foreach ($quartiers as $quartier)
                    <option value="{{ $quartier->id }}" @selected((string) ($filters['sector_id'] ?? '') === (string) $quartier->id)>{{ $quartier->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Du</label>
            <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full rounded-lg border-slate-200 text-sm">
        </div>
        <div class="flex items-end gap-2">
            <div class="flex-1">
                <label class="mb-1 block text-xs font-medium text-slate-500">Au</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full rounded-lg border-slate-200 text-sm">
            </div>
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">Filtrer</button>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Référence</th>
                        <th class="px-5 py-3">Catégorie</th>
                        <th class="px-5 py-3">Quartier</th>
                        <th class="px-5 py-3">Statut</th>
                        <th class="px-5 py-3">Citoyen</th>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($reports as $report)
                        <tr>
                            <td class="px-5 py-3 font-mono text-xs">{{ $report->reference }}</td>
                            <td class="px-5 py-3">{{ $report->category->label() }}</td>
                            <td class="px-5 py-3">{{ $report->sector?->name ?? '—' }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium" style="background: {{ $report->status->mapColor() }}22; color: {{ $report->status->mapColor() }}">
                                    {{ $report->status->label() }}
                                </span>
                            </td>
                            <td class="px-5 py-3">{{ $report->citizen?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $report->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('admin.municipality.reports.show', $report) }}" class="text-sky-600 hover:underline">Détail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-slate-500">Aucun signalement.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($reports->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $reports->links() }}</div>
        @endif
    </div>
@endsection
