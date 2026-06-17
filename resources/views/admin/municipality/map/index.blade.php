@extends('layouts.admin')

@section('title', 'Carte municipale')
@section('page_title', 'Carte SIG — Signalements')
@section('page_subtitle', 'Couche signalements citoyens Owendo')
@section('admin_page', 'municipality-map')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <style>
        .admin-map-fullscreen main { padding: 0 !important; }
        .admin-map-fullscreen header { margin-bottom: 0; }
    </style>
@endpush

@section('content')
    <div class="flex flex-wrap items-center gap-3 border-b border-slate-200 bg-white px-4 py-3 text-xs lg:px-6">
        <span class="inline-flex items-center gap-2 rounded-full px-3 py-1" style="background:#E5393522;color:#E53935">
            <span class="h-2 w-2 rounded-full" style="background:#E53935"></span> Nouveau
        </span>
        <span class="inline-flex items-center gap-2 rounded-full px-3 py-1" style="background:#FB8C0022;color:#FB8C00">
            <span class="h-2 w-2 rounded-full" style="background:#FB8C00"></span> En cours
        </span>
        <span class="inline-flex items-center gap-2 rounded-full px-3 py-1" style="background:#43A04722;color:#43A047">
            <span class="h-2 w-2 rounded-full" style="background:#43A047"></span> Résolu / Clôturé
        </span>
        <span id="map-count" class="ml-auto text-slate-500">Chargement…</span>
    </div>

    <div id="municipality-map" class="h-[calc(100vh-8.5rem)] min-h-[480px] w-full"></div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.documentElement.classList.add('admin-map-fullscreen');
        (function () {
            const center = [0.3380, 9.4710];
            const map = L.map('municipality-map').setView(center, 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap',
            }).addTo(map);

            const markers = [];

            fetch('{{ route('admin.municipality.map.geojson') }}', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
            })
                .then((r) => r.json())
                .then((geojson) => {
                    const features = geojson?.features ?? [];
                    document.getElementById('map-count').textContent = features.length + ' signalement(s)';

                    features.forEach((feature) => {
                        const [lng, lat] = feature.geometry.coordinates;
                        const p = feature.properties;
                        const color = p.color || '#E53935';
                        const icon = L.divIcon({
                            className: '',
                            html: `<span style="background:${color};width:14px;height:14px;display:block;border-radius:50%;border:2px solid white;box-shadow:0 1px 3px rgba(0,0,0,.4)"></span>`,
                            iconSize: [14, 14],
                            iconAnchor: [7, 7],
                        });
                        const marker = L.marker([lat, lng], { icon }).bindPopup(
                            `<strong>${p.reference}</strong><br>${p.title}<br>${p.category} — ${p.status}`
                        );
                        marker.addTo(map);
                        markers.push(marker);
                    });
                })
                .catch(() => {
                    document.getElementById('map-count').textContent = 'Erreur chargement API';
                });
        })();
    </script>
@endpush
