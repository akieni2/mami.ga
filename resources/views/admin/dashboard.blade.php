@extends('layouts.admin')

@section('title', 'Tableau de bord')
@section('page_title', 'Tableau de bord')
@section('page_subtitle', 'Vue d\'ensemble de la plateforme MAMI.GA')

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @include('admin.partials.stat-card', ['label' => 'Total chauffeurs', 'value' => $stats['total_drivers'], 'color' => 'slate'])
        @include('admin.partials.stat-card', ['label' => 'Chauffeurs en ligne', 'value' => $stats['online_drivers'], 'hint' => 'Disponibles avec GPS récent', 'color' => 'emerald'])
        @include('admin.partials.stat-card', ['label' => 'Courses actives', 'value' => $stats['active_rides'], 'hint' => 'En cours sur la plateforme', 'color' => 'sky'])
        @include('admin.partials.stat-card', ['label' => 'Courses terminées', 'value' => $stats['completed_rides'], 'color' => 'amber'])
    </div>

    <div class="mt-4 grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-1">
            <h2 class="text-sm font-semibold text-slate-800">Activité chauffeurs</h2>
            <dl class="mt-4 space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-slate-500">En ligne</dt>
                    <dd class="font-semibold text-emerald-600">{{ $stats['online_drivers'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">En course (occupés)</dt>
                    <dd class="font-semibold text-amber-600">{{ $stats['busy_drivers'] }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Total inscrits</dt>
                    <dd class="font-semibold text-slate-900">{{ $stats['total_drivers'] }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm lg:col-span-2">
            <div class="border-b border-slate-100 px-5 py-4">
                <h2 class="text-sm font-semibold text-slate-800">Dernières courses</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                        <tr>
                            <th class="px-5 py-3">#</th>
                            <th class="px-5 py-3">Client</th>
                            <th class="px-5 py-3">Chauffeur</th>
                            <th class="px-5 py-3">Statut</th>
                            <th class="px-5 py-3">Prix est.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($recentRides as $ride)
                            <tr>
                                <td class="px-5 py-3 font-medium">{{ $ride->id }}</td>
                                <td class="px-5 py-3">{{ $ride->client?->name ?? '—' }}</td>
                                <td class="px-5 py-3">{{ $ride->driver?->user?->name ?? '—' }}</td>
                                <td class="px-5 py-3">
                                    @include('admin.partials.status-badge', ['status' => $ride->status->value])
                                </td>
                                <td class="px-5 py-3">{{ $ride->estimated_price ? number_format($ride->estimated_price, 0, ',', ' ') . ' FCFA' : '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-8 text-center text-slate-500">Aucune course pour le moment.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
