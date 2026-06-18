@extends('layouts.admin')

@section('title', 'Fiscalité — Obligations')
@section('page_title', 'Fiscalité')
@section('page_subtitle', 'Obligations fiscales générées')

@section('content')
    @include('admin.municipality.fiscal.partials.nav', ['active' => 'obligations'])

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('admin.municipality.fiscal.obligations.generate') }}" class="mb-6">
        @csrf
        <button type="submit" class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-800">
            Générer les obligations (période courante)
        </button>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-5 py-3">Référence</th>
                    <th class="px-5 py-3">Opérateur</th>
                    <th class="px-5 py-3">Taxe</th>
                    <th class="px-5 py-3">Période</th>
                    <th class="px-5 py-3">Dû</th>
                    <th class="px-5 py-3">Solde</th>
                    <th class="px-5 py-3">Statut</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($obligations as $obligation)
                    <tr>
                        <td class="px-5 py-3 font-mono text-xs">{{ $obligation->reference }}</td>
                        <td class="px-5 py-3">{{ $obligation->operator?->public_id }}</td>
                        <td class="px-5 py-3 font-mono text-xs">{{ $obligation->taxType?->code }}</td>
                        <td class="px-5 py-3">{{ $obligation->period_start->format('d/m/Y') }} — {{ $obligation->period_end->format('d/m/Y') }}</td>
                        <td class="px-5 py-3">{{ number_format($obligation->amount_due, 0, ',', ' ') }}</td>
                        <td class="px-5 py-3">{{ number_format($obligation->balance_due, 0, ',', ' ') }}</td>
                        <td class="px-5 py-3">{{ $obligation->status->label() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-8 text-center text-slate-500">Aucune obligation. Lancez la génération après affectations et taux.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-slate-100 px-5 py-3">{{ $obligations->links() }}</div>
    </div>
@endsection
