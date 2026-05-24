@extends('layouts.admin')

@section('title', 'Chauffeurs')
@section('page_title', 'Chauffeurs')
@section('page_subtitle', 'Statuts en ligne / hors ligne / occupé — actualisation toutes les 10 s')
@section('admin_page', 'drivers')

@section('content')
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">ID</th>
                        <th class="px-5 py-3">Nom</th>
                        <th class="px-5 py-3">Téléphone</th>
                        <th class="px-5 py-3">Permis</th>
                        <th class="px-5 py-3">GPS (lat, lng)</th>
                        <th class="px-5 py-3">Véhicule</th>
                        <th class="px-5 py-3">Présence</th>
                        <th class="px-5 py-3">Note</th>
                        <th class="px-5 py-3">Dernière activité</th>
                    </tr>
                </thead>
                <tbody id="drivers-table-body" class="divide-y divide-slate-100">
                    @forelse ($drivers as $driver)
                        <tr>
                            <td class="px-5 py-3 font-medium">{{ $driver->id }}</td>
                            <td class="px-5 py-3">{{ $driver->user?->name ?? '—' }}</td>
                            <td class="px-5 py-3">{{ $driver->user?->phone ?? '—' }}</td>
                            <td class="px-5 py-3">{{ $driver->license_number }}</td>
                            <td class="px-5 py-3 font-mono text-xs">
                                @if ($driver->hasGpsPosition())
                                    {{ number_format((float) $driver->latitude, 5) }}, {{ number_format((float) $driver->longitude, 5) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @if ($driver->vehicle)
                                    {{ $driver->vehicle->brand }} {{ $driver->vehicle->model }}
                                    <span class="text-slate-400">({{ $driver->vehicle->plate_number }})</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                @include('admin.partials.status-badge', ['status' => $driver->presenceStatus()])
                            </td>
                            <td class="px-5 py-3">{{ number_format((float) $driver->rating, 1) }}</td>
                            <td class="px-5 py-3 text-slate-500">
                                {{ $driver->last_seen_at?->diffForHumans() ?? 'Jamais' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-10 text-center text-slate-500">Aucun chauffeur enregistré.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($drivers->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $drivers->links() }}
            </div>
        @endif
    </div>
@endsection
