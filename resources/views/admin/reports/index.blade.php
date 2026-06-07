@extends('layouts.admin')

@section('title', 'Rapports')
@section('page_title', 'Rapports')
@section('page_subtitle', 'Statistiques opérationnelles')
@section('admin_page', 'reports')

@section('content')
    <div class="mb-4 flex flex-wrap gap-2">
        @foreach (['day' => 'Jour', 'week' => 'Semaine', 'month' => 'Mois'] as $p => $label)
            <a href="{{ route('admin.reports.index', ['period' => $p]) }}"
               class="{{ $period === $p ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 hover:bg-slate-50' }} rounded-full border border-slate-200 px-4 py-1.5 text-sm font-medium transition">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @include('admin.partials.stat-card', ['label' => 'Courses créées', 'value' => $summary['rides_total'], 'color' => 'sky'])
        @include('admin.partials.stat-card', ['label' => 'Terminées', 'value' => $summary['rides_completed'], 'color' => 'emerald'])
        @include('admin.partials.stat-card', ['label' => 'Annulées', 'value' => $summary['rides_cancelled'], 'color' => 'slate'])
        @include('admin.partials.stat-card', ['label' => 'CA estimé', 'value' => number_format($summary['estimated_revenue'], 0, ',', ' ') . ' FCFA', 'color' => 'indigo'])
    </div>

    <div class="mt-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-sm font-semibold text-slate-800">Répartition par statut</h2>
        <dl class="mt-4 grid gap-3 sm:grid-cols-3 lg:grid-cols-6 text-sm">
            @foreach (['pending', 'accepted', 'arrived', 'started', 'completed', 'cancelled'] as $statusKey)
                <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                    <dt class="text-xs uppercase text-slate-500">{{ $statusKey }}</dt>
                    <dd class="mt-1 text-lg font-semibold text-slate-900">{{ $summary['rides_by_status'][$statusKey] ?? 0 }}</dd>
                </div>
            @endforeach
        </dl>
        <p class="mt-4 text-xs text-slate-500">
            Période : {{ \Illuminate\Support\Carbon::parse($summary['from'])->format('d/m/Y H:i') }}
            → {{ \Illuminate\Support\Carbon::parse($summary['to'])->format('d/m/Y H:i') }}
        </p>
    </div>
@endsection
