@extends('layouts.admin')

@section('title', 'Candidatures chauffeurs')
@section('page_title', 'Candidatures chauffeurs')
@section('page_subtitle', 'Enrôlement et validation des nouveaux chauffeurs')
@section('admin_page', 'driver-applications')

@section('content')
    <div class="mb-4 flex flex-wrap gap-2">
        @foreach ([
            null => 'Toutes',
            'pending' => 'En attente',
            'approved' => 'Approuvées',
            'rejected' => 'Rejetées',
        ] as $filterValue => $filterLabel)
            <a href="{{ route('admin.driver-applications.index', $filterValue ? ['status' => $filterValue] : []) }}"
               class="{{ ($status ?? null) === $filterValue || ($filterValue === null && empty($status)) ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 hover:bg-slate-50' }} rounded-full border border-slate-200 px-3 py-1 text-xs font-medium transition">
                {{ $filterLabel }}
            </a>
        @endforeach
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">ID</th>
                        <th class="px-5 py-3">Nom</th>
                        <th class="px-5 py-3">Téléphone</th>
                        <th class="px-5 py-3">Véhicule</th>
                        <th class="px-5 py-3">Statut</th>
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($applications as $application)
                        <tr>
                            <td class="px-5 py-3 font-medium">{{ $application->id }}</td>
                            <td class="px-5 py-3">{{ $application->fullName() }}</td>
                            <td class="px-5 py-3">{{ $application->phone }}</td>
                            <td class="px-5 py-3">{{ $application->vehicleLabel() }}</td>
                            <td class="px-5 py-3">
                                @include('admin.partials.status-badge', ['status' => $application->status->value])
                            </td>
                            <td class="px-5 py-3 text-slate-500">{{ $application->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('admin.driver-applications.show', $application) }}" class="text-sky-600 hover:underline">Détail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-slate-500">Aucune candidature.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($applications->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $applications->links() }}
            </div>
        @endif
    </div>
@endsection
