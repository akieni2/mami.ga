@extends('layouts.admin')

@section('title', 'Clients')
@section('page_title', 'Clients')
@section('page_subtitle', 'Liste des clients et volume de courses')
@section('admin_page', 'clients')

@section('content')
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">ID</th>
                        <th class="px-5 py-3">Nom</th>
                        <th class="px-5 py-3">Email</th>
                        <th class="px-5 py-3">Téléphone</th>
                        <th class="px-5 py-3">Courses</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($clients as $client)
                        <tr>
                            <td class="px-5 py-3 font-medium">{{ $client->id }}</td>
                            <td class="px-5 py-3">{{ $client->name }}</td>
                            <td class="px-5 py-3">{{ $client->email }}</td>
                            <td class="px-5 py-3">{{ $client->phone ?? '—' }}</td>
                            <td class="px-5 py-3 font-semibold">{{ $client->client_rides_count }}</td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('admin.clients.show', $client) }}" class="text-sky-600 hover:underline">Historique</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center text-slate-500">Aucun client enregistré.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($clients->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $clients->links() }}
            </div>
        @endif
    </div>
@endsection
