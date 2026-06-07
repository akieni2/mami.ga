@extends('layouts.admin')

@section('title', $driver->user?->name ?? 'Chauffeur #' . $driver->id)
@section('page_title', $driver->user?->name ?? 'Chauffeur #' . $driver->id)
@section('page_subtitle', 'Fiche chauffeur — position et disponibilité')
@section('admin_page', 'drivers')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
@endpush

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <a href="{{ route('admin.drivers.index') }}" class="text-sm text-sky-600 hover:underline">&larr; Retour aux chauffeurs</a>
        <a href="{{ route('admin.drivers.live', $driver) }}"
           class="inline-flex items-center rounded-lg bg-sky-600 px-4 py-2 text-sm font-medium text-white hover:bg-sky-700">
            Voir en temps réel
        </a>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-800">Informations</h2>
            <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-slate-500">Nom</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $driver->user?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Téléphone</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $driver->user?->phone ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Email</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $driver->user?->email ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Note</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ number_format((float) $driver->rating, 1) }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Statut</dt>
                    <dd class="mt-1">@include('admin.partials.status-badge', ['status' => $driver->status?->value ?? 'offline'])</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Disponibilité</dt>
                    <dd class="mt-1">@include('admin.partials.status-badge', ['status' => $driver->presenceStatus()])</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Dernière activité</dt>
                    <dd class="mt-1 text-slate-900">{{ $driver->last_seen_at?->diffForHumans() ?? 'Jamais' }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-800">Véhicule</h2>
            <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-slate-500">Marque / Modèle</dt>
                    <dd class="mt-1 font-medium text-slate-900">
                        @if ($driver->vehicle)
                            {{ $driver->vehicle->brand }} {{ $driver->vehicle->model }}
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">Plaque</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $driver->vehicle?->plate_number ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Couleur</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $driver->vehicle?->color ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Année</dt>
                    <dd class="mt-1 font-medium text-slate-900">{{ $driver->vehicle?->year ?? '—' }}</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="mt-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-sm font-semibold text-slate-800">Dernière position GPS</h2>
            <p class="font-mono text-xs text-slate-600">
                @if ($driver->hasGpsPosition())
                    {{ number_format((float) $driver->latitude, 5) }},
                    {{ number_format((float) $driver->longitude, 5) }}
                @else
                    Position inconnue
                @endif
            </p>
        </div>
        <div id="driver-detail-map" class="mt-4 h-80 w-full rounded-lg border border-slate-200"></div>
    </div>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        (function () {
            const hasPosition = @json($driver->hasGpsPosition());
            const center = hasPosition
                ? [{{ (float) $driver->latitude }}, {{ (float) $driver->longitude }}]
                : [0.4162, 9.4673];
            const zoom = hasPosition ? 15 : 13;

            const map = L.map('driver-detail-map').setView(center, zoom);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap',
            }).addTo(map);

            if (hasPosition) {
                const presence = @json($driver->presenceStatus());
                const color = presence === 'online' ? '#10b981' : (presence === 'busy' || presence === 'on_ride' ? '#f59e0b' : '#64748b');
                const html = `<span style="background:${color};width:14px;height:14px;display:block;border-radius:50%;border:2px solid white;box-shadow:0 1px 3px rgba(0,0,0,.4)"></span>`;
                const icon = L.divIcon({ className: '', html, iconSize: [14, 14], iconAnchor: [7, 7] });
                L.marker(center, { icon })
                    .addTo(map)
                    .bindPopup(@json($driver->user?->name ?? 'Chauffeur'));
            }
        })();
    </script>
@endpush
