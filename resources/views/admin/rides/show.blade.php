@extends('layouts.admin')

@section('title', 'Course #' . $ride->id)
@section('page_title', 'Course #' . $ride->id)
@section('page_subtitle', 'Détail opérationnel')
@section('admin_page', 'rides')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.rides.index') }}" class="text-sm text-sky-600 hover:underline">&larr; Retour aux courses</a>
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:col-span-2">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-sm font-semibold text-slate-800">Informations course</h2>
                @include('admin.partials.status-badge', ['status' => $ride->status->value])
            </div>

            <dl class="mt-4 grid gap-4 sm:grid-cols-2 text-sm">
                <div>
                    <dt class="text-slate-500">Client</dt>
                    <dd class="mt-1 font-medium text-slate-900">
                        @if ($ride->client)
                            <a href="{{ route('admin.clients.show', $ride->client) }}" class="text-sky-600 hover:underline">{{ $ride->client->name }}</a>
                            <span class="block text-xs text-slate-500">{{ $ride->client->phone ?? $ride->client->email }}</span>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">Chauffeur</dt>
                    <dd class="mt-1 font-medium text-slate-900">
                        {{ $ride->driver?->user?->name ?? '—' }}
                        @if ($ride->driver?->user?->phone)
                            <span class="block text-xs text-slate-500">{{ $ride->driver->user->phone }}</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">Véhicule</dt>
                    <dd class="mt-1 font-medium text-slate-900">
                        @if ($ride->driver?->vehicle)
                            {{ $ride->driver->vehicle->brand }} {{ $ride->driver->vehicle->model }}
                            <span class="text-slate-500">({{ $ride->driver->vehicle->plate_number }})</span>
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">Prix estimé</dt>
                    <dd class="mt-1 font-medium text-slate-900">
                        {{ $ride->estimated_price ? number_format($ride->estimated_price, 0, ',', ' ') . ' FCFA' : '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">Distance (tracking)</dt>
                    <dd class="mt-1 font-medium text-slate-900">
                        @if ($tracking['tracking']['distance_km'] !== null)
                            {{ number_format($tracking['tracking']['distance_km'], 2, ',', ' ') }} km
                        @else
                            —
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">ETA</dt>
                    <dd class="mt-1 font-medium text-slate-900">
                        @php $eta = $tracking['tracking']['eta_minutes'] ?? $tracking['tracking']['estimated_arrival']['eta_minutes'] ?? null; @endphp
                        {{ $eta !== null ? $eta . ' min' : '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">Pickup</dt>
                    <dd class="mt-1 font-mono text-xs text-slate-700">
                        {{ number_format((float) $ride->pickup_latitude, 5) }},
                        {{ number_format((float) $ride->pickup_longitude, 5) }}
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">Destination</dt>
                    <dd class="mt-1 font-mono text-xs text-slate-700">
                        {{ number_format((float) $ride->destination_latitude, 5) }},
                        {{ number_format((float) $ride->destination_longitude, 5) }}
                    </dd>
                </div>
                <div>
                    <dt class="text-slate-500">Créée le</dt>
                    <dd class="mt-1 text-slate-900">{{ $ride->created_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-500">Terminée le</dt>
                    <dd class="mt-1 text-slate-900">{{ $ride->completed_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                </div>
            </dl>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-semibold text-slate-800">Chauffeur (live)</h2>
            @if ($tracking['driver'])
                <dl class="mt-4 space-y-3 text-sm">
                    <div>
                        <dt class="text-slate-500">Présence</dt>
                        <dd class="mt-1">@include('admin.partials.status-badge', ['status' => $tracking['driver']['presence']])</dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Position GPS</dt>
                        <dd class="mt-1 font-mono text-xs">
                            @if ($tracking['driver']['latitude'] !== null)
                                {{ number_format($tracking['driver']['latitude'], 5) }},
                                {{ number_format($tracking['driver']['longitude'], 5) }}
                            @else
                                —
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-slate-500">Dernière activité</dt>
                        <dd class="mt-1 text-slate-900">
                            {{ $tracking['driver']['last_seen_at'] ? \Illuminate\Support\Carbon::parse($tracking['driver']['last_seen_at'])->diffForHumans() : '—' }}
                        </dd>
                    </div>
                </dl>
            @else
                <p class="mt-4 text-sm text-slate-500">Aucun chauffeur assigné.</p>
            @endif
        </div>
    </div>
@endsection
