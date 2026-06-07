@extends('layouts.admin')

@section('title', 'Carte opérationnelle')
@section('page_title', 'Carte opérationnelle')
@section('page_subtitle', 'Chauffeurs en ligne et en course — Reverb temps réel + polling 10 s')
@section('admin_page', 'map')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <style>
        .admin-map-fullscreen main { padding: 0 !important; }
        .admin-map-fullscreen header { margin-bottom: 0; }
    </style>
@endpush

@section('content')
    <div class="flex flex-wrap items-center gap-3 border-b border-slate-200 bg-white px-4 py-3 text-xs lg:px-6">
        <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-emerald-800">
            <span class="h-2 w-2 rounded-full bg-emerald-500"></span> En ligne
        </span>
        <span class="inline-flex items-center gap-2 rounded-full bg-amber-100 px-3 py-1 text-amber-800">
            <span class="h-2 w-2 rounded-full bg-amber-500"></span> En course
        </span>
        <span class="inline-flex items-center gap-2 rounded-full bg-slate-200 px-3 py-1 text-slate-700">
            <span class="h-2 w-2 rounded-full bg-slate-500"></span> Hors ligne
        </span>
        <span id="reverb-status" class="ml-auto text-slate-400">Reverb…</span>
    </div>

    <div id="live-map" class="h-[calc(100vh-8.5rem)] min-h-[480px] w-full"></div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://cdn.jsdelivr.net/npm/pusher-js@8.4.0/dist/web/pusher.min.js"></script>
    @php
        $reverbConfig = [
            'key' => config('broadcasting.connections.reverb.key'),
            'host' => config('broadcasting.connections.reverb.options.host'),
            'port' => (int) config('broadcasting.connections.reverb.options.port'),
            'scheme' => config('broadcasting.connections.reverb.options.scheme', 'http'),
            'prefix' => config('mami.broadcast_prefix', 'mami'),
        ];
    @endphp
    <script>
        window.mamiReverbConfig = @json($reverbConfig);
    </script>
    <script>
        document.documentElement.classList.add('admin-map-fullscreen');
        (function () {
            const defaultCenter = [0.4162, 9.4673];
            const map = L.map('live-map').setView(defaultCenter, 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            }).addTo(map);

            const markers = {};
            const driverMeta = {};

            const markerColor = (presence) => {
                if (presence === 'online') return '#10b981';
                if (presence === 'busy' || presence === 'on_ride') return '#f59e0b';
                return '#64748b';
            };

            const upsertMarker = (d) => {
                if (d.latitude == null || d.longitude == null) return;
                driverMeta[d.id] = { ...driverMeta[d.id], ...d };
                const meta = driverMeta[d.id];
                const html = `<span style="background:${markerColor(meta.presence)};width:14px;height:14px;display:block;border-radius:50%;border:2px solid white;box-shadow:0 1px 3px rgba(0,0,0,.4)"></span>`;
                const icon = L.divIcon({ className: '', html, iconSize: [14, 14], iconAnchor: [7, 7] });
                const popup = `<strong>${meta.name ?? 'Chauffeur'}</strong><br>Statut: ${meta.presence}<br>${meta.vehicle ?? ''}`;

                if (markers[d.id]) {
                    markers[d.id].setLatLng([d.latitude, d.longitude]);
                    markers[d.id].setIcon(icon);
                    markers[d.id].setPopupContent(popup);
                } else {
                    markers[d.id] = L.marker([d.latitude, d.longitude], { icon })
                        .addTo(map)
                        .bindPopup(popup);
                }
            };

            window.mamiUpdateMapMarkers = function (drivers) {
                const ids = new Set(drivers.map((d) => d.id));
                Object.keys(markers).forEach((id) => {
                    if (!ids.has(Number(id))) {
                        map.removeLayer(markers[id]);
                        delete markers[id];
                        delete driverMeta[id];
                    }
                });
                drivers.forEach(upsertMarker);
                window.mamiSubscribeDriverChannels?.(drivers);
            };

            window.mamiPatchDriverLocation = function (driverId, payload) {
                const meta = driverMeta[driverId] ?? { id: driverId, name: 'Chauffeur #' + driverId };
                upsertMarker({
                    id: driverId,
                    name: meta.name,
                    vehicle: meta.vehicle,
                    presence: payload.presence ?? meta.presence ?? 'offline',
                    latitude: payload.latitude,
                    longitude: payload.longitude,
                });
            };

            (function initReverb() {
                const cfg = window.mamiReverbConfig;
                const statusEl = document.getElementById('reverb-status');
                const subscribed = new Set();

                if (!cfg?.key || typeof Pusher === 'undefined') {
                    if (statusEl) statusEl.textContent = 'Polling actif';
                    return;
                }

                const setStatus = (text, ok) => {
                    if (!statusEl) return;
                    statusEl.textContent = text;
                    statusEl.className = ok ? 'ml-auto text-emerald-600' : 'ml-auto text-slate-400';
                };

                const useTls = cfg.scheme === 'https';
                const pusher = new Pusher(cfg.key, {
                    wsHost: cfg.host,
                    wsPort: cfg.port,
                    wssPort: cfg.port,
                    forceTLS: useTls,
                    enabledTransports: ['ws', 'wss'],
                    cluster: 'mt1',
                    disableStats: true,
                });

                pusher.connection.bind('connected', () => setStatus('Reverb connecté', true));
                pusher.connection.bind('unavailable', () => setStatus('Reverb indisponible (polling)', false));
                pusher.connection.bind('failed', () => setStatus('Reverb échec (polling)', false));

                window.mamiSubscribeDriverChannels = function (drivers) {
                    drivers.forEach((d) => {
                        if (subscribed.has(d.id)) return;
                        subscribed.add(d.id);
                        const channel = pusher.subscribe(cfg.prefix + '.drivers.' + d.id);
                        channel.bind('DriverLocationUpdated', (envelope) => {
                            const payload = envelope?.payload ?? envelope;
                            if (payload?.driver_id) {
                                window.mamiPatchDriverLocation(payload.driver_id, payload);
                            }
                        });
                    });
                };
            })();
        })();
    </script>
@endpush
