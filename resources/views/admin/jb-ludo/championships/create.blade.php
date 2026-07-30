@extends('layouts.admin')

@section('title', 'Nouveau championnat')
@section('page_title', 'Nouveau championnat')
@section('page_subtitle', 'Parametrez le nombre de participants et la date de depart')

@section('content')
    @include('admin.jb-ludo.partials.nav', ['active' => 'championships'])

    <form method="POST" action="{{ route('admin.jb-ludo.championships.store') }}"
          class="max-w-3xl space-y-5 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        @csrf

        <div>
            <label class="block text-sm font-medium text-slate-700" for="name">Nom du championnat</label>
            <input id="name" name="name" value="{{ old('name') }}" required maxlength="160"
                   class="mt-1 w-full rounded-lg border-slate-200 text-sm"
                   placeholder="Ex. Grand Championnat JB Ludo Owendo">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700" for="max_participants">Nombre maximum de participants</label>
            <input id="max_participants" name="max_participants" type="number" min="2" max="5000"
                   value="{{ old('max_participants', 1000) }}"
                   class="mt-1 rounded-lg border-slate-200 text-sm">
            @error('max_participants') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="block text-sm font-medium text-slate-700" for="registration_closes_at">Fin des inscriptions</label>
                <input id="registration_closes_at" name="registration_closes_at" type="datetime-local"
                       value="{{ old('registration_closes_at') }}"
                       class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                @error('registration_closes_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700" for="starts_at">Debut prevu</label>
                <input id="starts_at" name="starts_at" type="datetime-local" value="{{ old('starts_at') }}"
                       class="mt-1 w-full rounded-lg border-slate-200 text-sm">
                @error('starts_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700" for="description">Description</label>
            <textarea id="description" name="description" rows="4"
                      class="mt-1 w-full rounded-lg border-slate-200 text-sm">{{ old('description') }}</textarea>
            @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Creer
            </button>
            <a href="{{ route('admin.jb-ludo.championships.index') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm hover:bg-slate-50">
                Annuler
            </a>
        </div>
    </form>
@endsection
