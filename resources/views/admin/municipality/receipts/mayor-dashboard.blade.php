@extends('layouts.admin')

@section('title', 'Quittances — Tableau de bord maire')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Quittances officielles</h1>
            <p class="text-slate-600">KPI recouvrement — Sprint 3</p>
        </div>
        <form method="GET" class="flex items-center gap-2">
            <input type="date" name="date" value="{{ $date }}" class="rounded border-slate-300 text-sm">
            <button type="submit" class="rounded bg-slate-800 px-3 py-2 text-sm text-white">Filtrer</button>
        </form>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-lg border bg-white p-4 shadow-sm">
            <p class="text-sm text-slate-500">Quittances émises</p>
            <p class="text-3xl font-bold">{{ $data['receipts_issued'] }}</p>
        </div>
        <div class="rounded-lg border bg-white p-4 shadow-sm">
            <p class="text-sm text-slate-500">Quittances annulées</p>
            <p class="text-3xl font-bold text-red-600">{{ $data['receipts_annulled'] }}</p>
        </div>
        <div class="rounded-lg border bg-white p-4 shadow-sm">
            <p class="text-sm text-slate-500">Montant encaissé</p>
            <p class="text-3xl font-bold">{{ number_format($data['collected_today_xaf'], 0, ',', ' ') }} XAF</p>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-lg border bg-white shadow-sm">
            <div class="border-b px-4 py-3 font-semibold">Par quartier</div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left">
                        <tr>
                            <th class="px-4 py-2">Quartier</th>
                            <th class="px-4 py-2">Encaissements</th>
                            <th class="px-4 py-2">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data['by_quartier'] as $row)
                            <tr class="border-t">
                                <td class="px-4 py-2">{{ $row->quartier }}</td>
                                <td class="px-4 py-2">{{ $row->count }}</td>
                                <td class="px-4 py-2">{{ number_format($row->total, 0, ',', ' ') }} XAF</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-6 text-center text-slate-500">Aucune donnée</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-lg border bg-white shadow-sm">
            <div class="border-b px-4 py-3 font-semibold">Par agent</div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left">
                        <tr>
                            <th class="px-4 py-2">Agent</th>
                            <th class="px-4 py-2">Encaissements</th>
                            <th class="px-4 py-2">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data['by_agent'] as $row)
                            <tr class="border-t">
                                <td class="px-4 py-2">{{ $row->agent?->name ?? '—' }}</td>
                                <td class="px-4 py-2">{{ $row->count }}</td>
                                <td class="px-4 py-2">{{ number_format($row->total, 0, ',', ' ') }} XAF</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-6 text-center text-slate-500">Aucune donnée</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-lg border bg-white shadow-sm">
            <div class="border-b px-4 py-3 font-semibold">Par taxe</div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left">
                        <tr>
                            <th class="px-4 py-2">Taxe</th>
                            <th class="px-4 py-2">Lignes</th>
                            <th class="px-4 py-2">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($data['by_tax'] as $row)
                            <tr class="border-t">
                                <td class="px-4 py-2">{{ $row->tax_name }} ({{ $row->tax_code }})</td>
                                <td class="px-4 py-2">{{ $row->count }}</td>
                                <td class="px-4 py-2">{{ number_format($row->total, 0, ',', ' ') }} XAF</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-6 text-center text-slate-500">Aucune donnée</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
