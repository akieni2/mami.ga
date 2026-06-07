@extends('layouts.admin')

@section('title', 'Chauffeurs')
@section('page_title', 'Chauffeurs')
@section('page_subtitle', 'Statut, disponibilité, note et dernière position — actualisation toutes les 10 s')
@section('admin_page', 'drivers')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
@endpush

@section('content')
    <div id="drivers-mini-map" class="mb-4 h-64 w-full rounded-xl border border-slate-200 shadow-sm"></div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">ID</th>
                        <th class="px-5 py-3">Nom</th>
                        <th class="px-5 py-3">Téléphone</th>
                        <th class="px-5 py-3">Statut</th>
                        <th class="px-5 py-3">Disponibilité</th>
                        <th class="px-5 py-3">Dernière position</th>
                        <th class="px-5 py-3">Véhicule</th>
                        <th class="px-5 py-3">Note</th>
                        <th class="px-5 py-3">Dernière activité</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody id="drivers-table-body" class="divide-y divide-slate-100">
                    @forelse ($drivers as $driver)
                        <tr>
                            <td class="px-5 py-3 font-medium">{{ $driver->id }}</td>
                            <td class="px-5 py-3">
                                <a href="{{ route('admin.drivers.show', $driver) }}" class="font-medium text-sky-600 hover:underline">
                                    {{ $driver->user?->name ?? '—' }}
                                </a>
                            </td>
                            <td class="px-5 py-3">{{ $driver->user?->phone ?? '—' }}</td>
                            <td class="px-5 py-3">
                                @include('admin.partials.status-badge', ['status' => $driver->status?->value ?? 'offline'])
                            </td>
                            <td class="px-5 py-3">
                                @include('admin.partials.status-badge', ['status' => $driver->presenceStatus()])
                            </td>
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
                            <td class="px-5 py-3">{{ number_format((float) $driver->rating, 1) }}</td>
                            <td class="px-5 py-3 text-slate-500">
                                {{ $driver->last_seen_at?->diffForHumans() ?? 'Jamais' }}
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('admin.drivers.show', $driver) }}" class="text-sky-600 hover:underline">Fiche</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-5 py-10 text-center text-slate-500">Aucun chauffeur enregistré.</td>
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

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        (function () {
            const map = L.map('drivers-mini-map').setView([0.4162, 9.4673], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap',
            }).addTo(map);

            const markers = {};
            const markerColor = (presence) => {
                if (presence === 'online') return '#10b981';
                if (presence === 'busy' || presence === 'on_ride') return '#f59e0b';
                return '#64748b';
            };

            window.mamiUpdateDriversMiniMap = function (drivers) {
                const ids = new Set(drivers.map((d) => d.id));
                Object.keys(markers).forEach((id) => {
                    if (!ids.has(Number(id))) {
                        map.removeLayer(markers[id]);
                        delete markers[id];
                    }
                });
                drivers.forEach((d) => {
                    if (d.latitude == null || d.longitude == null) return;
                    const html = `<span style="background:${markerColor(d.presence)};width:12px;height:12px;display:block;border-radius:50%;border:2px solid white"></span>`;
                    const icon = L.divIcon({ className: '', html, iconSize: [12, 12], iconAnchor: [6, 6] });
                    if (markers[d.id]) {
                        markers[d.id].setLatLng([d.latitude, d.longitude]);
                        markers[d.id].setIcon(icon);
                    } else {
                        markers[d.id] = L.marker([d.latitude, d.longitude], { icon }).addTo(map)
                            .bindPopup(`<strong>${d.name}</strong><br>${d.presence}`);
                    }
                });
            };
        })();
    </script>
@endpush
