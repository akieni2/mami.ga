@extends('layouts.admin')

@section('title', 'Courses')
@section('page_title', 'Courses')
@section('page_subtitle', 'Liste des courses avec coordonnées pickup et destination')
@section('admin_page', 'rides')

@section('content')
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">ID</th>
                        <th class="px-5 py-3">Client</th>
                        <th class="px-5 py-3">Chauffeur</th>
                        <th class="px-5 py-3">Statut</th>
                        <th class="px-5 py-3">Pickup (lat, lng)</th>
                        <th class="px-5 py-3">Destination (lat, lng)</th>
                        <th class="px-5 py-3">Prix est.</th>
                        <th class="px-5 py-3">Créée</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rides as $ride)
                        <tr>
                            <td class="px-5 py-3 font-medium">{{ $ride->id }}</td>
                            <td class="px-5 py-3">{{ $ride->client?->name ?? '—' }}</td>
                            <td class="px-5 py-3">{{ $ride->driver?->user?->name ?? '—' }}</td>
                            <td class="px-5 py-3">
                                @include('admin.partials.status-badge', ['status' => $ride->status->value])
                            </td>
                            <td class="px-5 py-3 font-mono text-xs">
                                {{ number_format((float) $ride->pickup_latitude, 5) }},
                                {{ number_format((float) $ride->pickup_longitude, 5) }}
                            </td>
                            <td class="px-5 py-3 font-mono text-xs">
                                {{ number_format((float) $ride->destination_latitude, 5) }},
                                {{ number_format((float) $ride->destination_longitude, 5) }}
                            </td>
                            <td class="px-5 py-3">
                                {{ $ride->estimated_price ? number_format($ride->estimated_price, 0, ',', ' ') . ' FCFA' : '—' }}
                            </td>
                            <td class="px-5 py-3 text-slate-500">{{ $ride->created_at?->format('d/m/Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10 text-center text-slate-500">Aucune course enregistrée.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($rides->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $rides->links() }}
            </div>
        @endif
    </div>
@endsection
