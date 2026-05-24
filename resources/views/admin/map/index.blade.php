@extends('layouts.admin')

@section('title', 'Carte live')
@section('page_title', 'Carte live')
@section('page_subtitle', 'OpenStreetMap + Leaflet — marqueurs chauffeurs (refresh 10 s)')
@section('admin_page', 'map')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
@endpush

@section('content')
    <div class="mb-4 flex flex-wrap gap-3 text-xs">
        <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-emerald-800">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span> En ligne
        </span>
        <span class="inline-flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1 text-amber-800">
            <span class="h-2 w-2 rounded-full bg-amber-500"></span> Occupé
        </span>
        <span class="inline-flex items-center gap-2 rounded-full bg-slate-200 px-3 py-1 text-slate-700">
            <span class="h-2 w-2 rounded-full bg-slate-500"></span> Hors ligne
        </span>
    </div>

    <div id="live-map" class="h-[calc(100vh-12rem)] min-h-[400px] w-full rounded-xl border border-slate-200 shadow-sm"></div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        (function () {
            const defaultCenter = [0.4162, 9.4673];
            const map = L.map('live-map').setView(defaultCenter, 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            }).addTo(map);

            const markers = {};

            const markerColor = (presence) => {
                if (presence === 'online') return '#10b981';
                if (presence === 'busy') return '#f59e0b';
                return '#64748b';
            };

            window.mamiUpdateMapMarkers = function (drivers) {
                const ids = new Set(drivers.map((d) => d.id));

                Object.keys(markers).forEach((id) => {
                    if (!ids.has(Number(id))) {
                        map.removeLayer(markers[id]);
                        delete markers[id];
                    }
                });

                drivers.forEach((d) => {
                    if (d.latitude == null || d.longitude == null) return;

                    const html = `<span style="background:${markerColor(d.presence)};width:14px;height:14px;display:block;border-radius:50%;border:2px solid white;box-shadow:0 1px 3px rgba(0,0,0,.4)"></span>`;
                    const icon = L.divIcon({ className: '', html, iconSize: [14, 14], iconAnchor: [7, 7] });

                    if (markers[d.id]) {
                        markers[d.id].setLatLng([d.latitude, d.longitude]);
                        markers[d.id].setIcon(icon);
                        markers[d.id].setPopupContent(
                            `<strong>${d.name}</strong><br>Statut: ${d.presence}<br>${d.vehicle ?? ''}`
                        );
                    } else {
                        markers[d.id] = L.marker([d.latitude, d.longitude], { icon })
                            .addTo(map)
                            .bindPopup(`<strong>${d.name}</strong><br>Statut: ${d.presence}<br>${d.vehicle ?? ''}`);
                    }
                });
            };
        })();
    </script>
@endpush
