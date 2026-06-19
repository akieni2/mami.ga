@extends('layouts.admin')

@section('title', 'Utilisateurs')
@section('page_title', 'Utilisateurs')
@section('page_subtitle', 'Consultation des comptes et rôles')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap gap-2">
            <input type="search" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Nom, email, téléphone"
                   class="rounded-lg border-slate-200 text-sm">
            <select name="role" class="rounded-lg border-slate-200 text-sm">
                <option value="">Tous les rôles</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->slug }}" @selected(($filters['role'] ?? '') === $role->slug)>{{ $role->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50">Filtrer</button>
        </form>
        <a href="{{ route('admin.users.agents.create') }}"
           class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
            Créer un agent municipal
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-5 py-3">ID</th>
                    <th class="px-5 py-3">Nom</th>
                    <th class="px-5 py-3">Email</th>
                    <th class="px-5 py-3">Téléphone</th>
                    <th class="px-5 py-3">Rôles</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($users as $user)
                    <tr>
                        <td class="px-5 py-3">{{ $user->id }}</td>
                        <td class="px-5 py-3 font-medium">{{ $user->name }}</td>
                        <td class="px-5 py-3">{{ $user->email }}</td>
                        <td class="px-5 py-3">{{ $user->phone ?? '—' }}</td>
                        <td class="px-5 py-3">
                            <div class="flex flex-wrap gap-1">
                                @forelse ($user->roles as $role)
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-700">{{ $role->name }}</span>
                                @empty
                                    <span class="text-slate-400">—</span>
                                @endforelse
                                @if ($user->is_admin)
                                    <span class="rounded-full bg-sky-100 px-2 py-0.5 text-xs text-sky-800">Admin web</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('admin.users.show', $user) }}" class="text-sky-600 hover:underline">Détail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">Aucun utilisateur.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($users->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $users->links() }}</div>
        @endif
    </div>
@endsection
