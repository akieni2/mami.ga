@extends('layouts.admin')

@section('title', 'Utilisateur — '.$user->name)
@section('page_title', $user->name)
@section('page_subtitle', 'Fiche utilisateur et rôles')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.users.index') }}" class="text-sm text-sky-600 hover:underline">← Retour à la liste</a>
    </div>

    <div class="mb-6 grid gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-3 text-sm font-semibold text-slate-700">Informations</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-slate-500">ID</dt><dd>{{ $user->id }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Email</dt><dd>{{ $user->email }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Téléphone</dt><dd>{{ $user->phone ?? '—' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Admin web</dt><dd>{{ $user->is_admin ? 'Oui' : 'Non' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Chauffeur</dt><dd>{{ $user->isDriver() ? 'Oui' : 'Non' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-500">Inscrit le</dt><dd>{{ $user->created_at?->format('d/m/Y H:i') }}</dd></div>
            </dl>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5">
            <h2 class="mb-3 text-sm font-semibold text-slate-700">Rôles attribués</h2>
            <ul class="mb-4 space-y-2">
                @forelse ($user->roles as $role)
                    <li class="flex items-center justify-between gap-2 text-sm">
                        <span>{{ $role->name }} <span class="font-mono text-xs text-slate-400">({{ $role->slug }})</span></span>
                        <form method="POST" action="{{ route('admin.users.roles.detach', [$user, $role->slug]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-rose-600 hover:underline">Retirer</button>
                        </form>
                    </li>
                @empty
                    <li class="text-sm text-slate-500">Aucun rôle.</li>
                @endforelse
            </ul>

            <form method="POST" action="{{ route('admin.users.roles.attach', $user) }}" class="flex gap-2">
                @csrf
                <select name="role_slug" required class="min-w-0 flex-1 rounded-lg border-slate-200 text-sm @error('role_slug') border-rose-400 @enderror">
                    <option value="">Ajouter un rôle…</option>
                    @foreach ($allRoles as $role)
                        @continue($user->hasRole($role->slug))
                        <option value="{{ $role->slug }}">{{ $role->name }}</option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-lg bg-slate-900 px-3 py-2 text-sm text-white">Ajouter</button>
            </form>
            @include('admin.partials.field-error', ['field' => 'role_slug'])
        </div>
    </div>
@endsection
