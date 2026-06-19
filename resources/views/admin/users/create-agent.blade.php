@extends('layouts.admin')

@section('title', 'Créer un agent municipal')
@section('page_title', 'Créer un agent municipal')
@section('page_subtitle', 'Compte terrain avec rôle municipal_agent')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.users.index') }}" class="text-sm text-sky-600 hover:underline">← Retour à la liste</a>
    </div>

    <div class="max-w-xl rounded-xl border border-slate-200 bg-white p-5">
        <p class="mb-4 text-sm text-slate-600">
            L'agent pourra se connecter sur l'application mobile avec l'email et le mot de passe définis ci-dessous.
            Le rôle <span class="font-mono text-xs">municipal_agent</span> sera attribué automatiquement.
        </p>

        <form method="POST" action="{{ route('admin.users.agents.store') }}" class="space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Nom complet</label>
                <input type="text" name="name" required value="{{ old('name') }}"
                       class="w-full rounded-lg border-slate-200 text-sm @error('name') border-rose-400 @enderror">
                @include('admin.partials.field-error', ['field' => 'name'])
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                <input type="email" name="email" required value="{{ old('email') }}"
                       class="w-full rounded-lg border-slate-200 text-sm @error('email') border-rose-400 @enderror">
                @include('admin.partials.field-error', ['field' => 'email'])
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Téléphone</label>
                <input type="text" name="phone" value="{{ old('phone') }}"
                       class="w-full rounded-lg border-slate-200 text-sm @error('phone') border-rose-400 @enderror">
                @include('admin.partials.field-error', ['field' => 'phone'])
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Mot de passe</label>
                <input type="password" name="password" required minlength="8"
                       class="w-full rounded-lg border-slate-200 text-sm @error('password') border-rose-400 @enderror">
                @include('admin.partials.field-error', ['field' => 'password'])
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium text-slate-700">Confirmer le mot de passe</label>
                <input type="password" name="password_confirmation" required minlength="8"
                       class="w-full rounded-lg border-slate-200 text-sm">
            </div>
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">
                Créer l'agent municipal
            </button>
        </form>
    </div>
@endsection
