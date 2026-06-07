@extends('layouts.admin')

@section('title', 'Live — ' . ($driver->user?->name ?? 'Chauffeur #' . $driver->id))
@section('page_title', $driver->user?->name ?? 'Chauffeur #' . $driver->id)
@section('page_subtitle', 'Suivi temps réel — Reverb + polling 10 s')
@section('admin_page', 'driver-live')
@section('live_endpoint', route('admin.live.driver', $driver))

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <style>
        .admin-map-fullscreen main { padding: 0 !important; }
        .admin-map-fullscreen header { margin-bottom: 0; }
    </style>
@endpush

@section('content')
    <div class="flex flex-wrap items-center gap-3 border-b border-slate-200 bg-white px-4 py-3 text-xs lg:px-6">
        <a href="{{ route('admin.drivers.show', $driver) }}" class="text-sky-600 hover:underline">&larr; Fiche chauffeur</a>
        <span class="text-slate-500">|</span>
        <span id="live-driver-presence" class="inline-flex items-center gap-2 rounded-full bg-slate-200 px-3 py-1 text-slate-700">
            <span class="h-2 w-2 rounded-full bg-slate-500"></span>
            <span id="live-driver-presence-label">{{ $driver->presenceStatus() }}</span>
        </span>
        <span id="live-driver-coords" class="font-mono text-slate-500">
            @if ($driver->hasGpsPosition())
                {{ number_format((float) $driver->latitude, 5) }}, {{ number_format((float) $driver->longitude, 5) }}
            @else
                Position inconnue
            @endif
        </span>
        <span id="reverb-status" class="ml-auto text-slate-400">Reverb…</span>
    </div>

    <div id="driver-live-map" class="h-[calc(100vh-8.5rem)] min-h-[480px] w-full"></div>
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
            'driver_id' => $driver->id,
        ];
    @endphp
    <script>
        window.mamiReverbConfig = @json($reverbConfig);
        window.mamiLiveEndpoint = @json(route('admin.live.driver', $driver));
        window.mamiDriverLiveMeta = @json([
            'id' => $driver->id,
            'name' => $driver->user?->name ?? 'Chauffeur #'.$driver->id,
            'vehicle' => $driver->vehicle
                ? $driver->vehicle->brand.' '.$driver->vehicle->model.' ('.$driver->vehicle->plate_number.')'
                : null,
            'latitude' => $driver->latitude !== null ? (float) $driver->latitude : null,
            'longitude' => $driver->longitude !== null ? (float) $driver->longitude : null,
            'presence' => $driver->presenceStatus(),
        ]);
    </script>
    <script>
        document.documentElement.classList.add('admin-map-fullscreen');
        (function () {
            const meta = window.mamiDriverLiveMeta ?? {};
            const defaultCenter = [0.4162, 9.4673];
            const initial = meta.latitude != null && meta.longitude != null
                ? [meta.latitude, meta.longitude]
                : defaultCenter;
            const map = L.map('driver-live-map').setView(initial, meta.latitude != null ? 15 : 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap',
            }).addTo(map);

            let marker = null;

            const markerColor = (presence) => {
                if (presence === 'online') return '#10b981';
                if (presence === 'busy' || presence === 'on_ride') return '#f59e0b';
                return '#64748b';
            };

            const updatePresenceBadge = (presence) => {
                const wrap = document.getElementById('live-driver-presence');
                const label = document.getElementById('live-driver-presence-label');
                if (!wrap || !label) return;
                const colors = {
                    online: ['bg-emerald-100', 'text-emerald-800', 'bg-emerald-500'],
                    busy: ['bg-amber-100', 'text-amber-800', 'bg-amber-500'],
                    on_ride: ['bg-amber-100', 'text-amber-800', 'bg-amber-500'],
                };
                const palette = colors[presence] ?? ['bg-slate-200', 'text-slate-700', 'bg-slate-500'];
                wrap.className = `inline-flex items-center gap-2 rounded-full px-3 py-1 ${palette[0]} ${palette[1]}`;
                wrap.querySelector('span').className = `h-2 w-2 rounded-full ${palette[2]}`;
                label.textContent = presence;
            };

            window.mamiUpdateSingleDriverMarker = function (driver) {
                if (!driver || driver.latitude == null || driver.longitude == null) return;

                const coordsEl = document.getElementById('live-driver-coords');
                if (coordsEl) {
                    coordsEl.textContent = Number(driver.latitude).toFixed(5) + ', ' + Number(driver.longitude).toFixed(5);
                }

                if (driver.presence) updatePresenceBadge(driver.presence);

                const html = `<span style="background:${markerColor(driver.presence)};width:16px;height:16px;display:block;border-radius:50%;border:2px solid white;box-shadow:0 1px 4px rgba(0,0,0,.4)"></span>`;
                const icon = L.divIcon({ className: '', html, iconSize: [16, 16], iconAnchor: [8, 8] });
                const popup = `<strong>${driver.name ?? meta.name}</strong><br>Statut: ${driver.presence ?? '—'}<br>${driver.vehicle ?? meta.vehicle ?? ''}`;

                if (marker) {
                    marker.setLatLng([driver.latitude, driver.longitude]);
                    marker.setIcon(icon);
                    marker.setPopupContent(popup);
                } else {
                    marker = L.marker([driver.latitude, driver.longitude], { icon }).addTo(map).bindPopup(popup);
                }

                map.panTo([driver.latitude, driver.longitude], { animate: true });
            };

            if (meta.latitude != null && meta.longitude != null) {
                window.mamiUpdateSingleDriverMarker(meta);
            }

            (function initReverb() {
                const cfg = window.mamiReverbConfig;
                const statusEl = document.getElementById('reverb-status');

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

                const channel = pusher.subscribe(cfg.prefix + '.drivers.' + cfg.driver_id);
                channel.bind('DriverLocationUpdated', (envelope) => {
                    const payload = envelope?.payload ?? envelope;
                    if (payload?.latitude == null || payload?.longitude == null) return;
                    window.mamiUpdateSingleDriverMarker({
                        id: cfg.driver_id,
                        name: meta.name,
                        vehicle: meta.vehicle,
                        presence: payload.presence,
                        latitude: payload.latitude,
                        longitude: payload.longitude,
                    });
                });
            })();
        })();
    </script>
@endpush
