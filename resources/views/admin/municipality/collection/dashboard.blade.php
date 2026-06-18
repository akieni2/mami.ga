@extends('layouts.admin')

@section('title', 'Recouvrement terrain')

@section('content')
<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Recouvrement terrain</h1>
            <p class="text-slate-600">Sessions de caisse et encaissements — Sprint 2</p>
        </div>
        <form method="GET" class="flex items-center gap-2">
            <input type="date" name="date" value="{{ $date }}" class="rounded border-slate-300 text-sm">
            <button type="submit" class="rounded bg-slate-800 px-3 py-2 text-sm text-white">Filtrer</button>
        </form>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-lg border bg-white p-4 shadow-sm">
            <p class="text-sm text-slate-500">Sessions ouvertes</p>
            <p class="text-3xl font-bold">{{ $openSessions->count() }}</p>
        </div>
        <div class="rounded-lg border bg-white p-4 shadow-sm">
            <p class="text-sm text-slate-500">Collecté le {{ $date }}</p>
            <p class="text-3xl font-bold">{{ number_format($collectedToday, 0, ',', ' ') }} XAF</p>
        </div>
        <div class="rounded-lg border bg-white p-4 shadow-sm">
            <p class="text-sm text-slate-500">Agents actifs</p>
            <p class="text-3xl font-bold">{{ $byAgent->count() }}</p>
        </div>
    </div>

    <div class="rounded-lg border bg-white shadow-sm">
        <div class="border-b px-4 py-3 font-semibold">Sessions ouvertes</div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left">
                    <tr>
                        <th class="px-4 py-2">Référence</th>
                        <th class="px-4 py-2">Agent</th>
                        <th class="px-4 py-2">Ouverte</th>
                        <th class="px-4 py-2">Attendu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($openSessions as $session)
                        <tr class="border-t">
                            <td class="px-4 py-2 font-mono">{{ $session->reference }}</td>
                            <td class="px-4 py-2">{{ $session->agent?->name }}</td>
                            <td class="px-4 py-2">{{ $session->opened_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2">{{ number_format($session->expected_amount_xaf, 0, ',', ' ') }} XAF</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-slate-500">Aucune session ouverte</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-lg border bg-white shadow-sm">
            <div class="border-b px-4 py-3 font-semibold">Par agent ({{ $date }})</div>
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
                        @forelse ($byAgent as $row)
                            <tr class="border-t">
                                <td class="px-4 py-2">{{ $row->agent?->name ?? '—' }}</td>
                                <td class="px-4 py-2">{{ $row->count }}</td>
                                <td class="px-4 py-2">{{ number_format($row->total, 0, ',', ' ') }} XAF</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-6 text-center text-slate-500">Aucun encaissement</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-lg border bg-white shadow-sm">
            <div class="border-b px-4 py-3 font-semibold">Par jour (14 derniers jours)</div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left">
                        <tr>
                            <th class="px-4 py-2">Jour</th>
                            <th class="px-4 py-2">Opérations</th>
                            <th class="px-4 py-2">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($byDay as $row)
                            <tr class="border-t">
                                <td class="px-4 py-2">{{ $row->day }}</td>
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
