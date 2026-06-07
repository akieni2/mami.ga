@extends('layouts.admin')

@section('title', $user->name)
@section('page_title', $user->name)
@section('page_subtitle', 'Historique des courses client')
@section('admin_page', 'clients')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.clients.index') }}" class="text-sm text-sky-600 hover:underline">&larr; Retour aux clients</a>
    </div>

    <div class="mb-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <dl class="grid gap-4 sm:grid-cols-3 text-sm">
            <div>
                <dt class="text-slate-500">Email</dt>
                <dd class="mt-1 font-medium">{{ $user->email }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Téléphone</dt>
                <dd class="mt-1 font-medium">{{ $user->phone ?? '—' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Total courses</dt>
                <dd class="mt-1 text-2xl font-bold text-slate-900">{{ $ridesCount }}</dd>
            </div>
        </dl>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h2 class="text-sm font-semibold text-slate-800">Historique</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">ID</th>
                        <th class="px-5 py-3">Chauffeur</th>
                        <th class="px-5 py-3">Statut</th>
                        <th class="px-5 py-3">Prix est.</th>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($rides as $ride)
                        <tr>
                            <td class="px-5 py-3 font-medium">{{ $ride->id }}</td>
                            <td class="px-5 py-3">{{ $ride->driver?->user?->name ?? '—' }}</td>
                            <td class="px-5 py-3">
                                @include('admin.partials.status-badge', ['status' => $ride->status->value])
                            </td>
                            <td class="px-5 py-3">
                                {{ $ride->estimated_price ? number_format($ride->estimated_price, 0, ',', ' ') . ' FCFA' : '—' }}
                            </td>
                            <td class="px-5 py-3 text-slate-500">{{ $ride->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('admin.rides.show', $ride) }}" class="text-sky-600 hover:underline">Détail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-slate-500">Aucune course pour ce client.</td>
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
