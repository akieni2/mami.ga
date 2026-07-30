@extends('layouts.admin')

@section('title', 'Nouveau dossier public')
@section('page_title', 'Nouveau dossier public')
@section('page_subtitle', 'Ajoutez PDF, photos ou videos, puis imprimez le QR code')

@section('content')
    <form method="POST" action="{{ route('admin.document-shares.store') }}" enctype="multipart/form-data"
          class="max-w-3xl space-y-5 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        @csrf

        <div>
            <label for="title" class="block text-sm font-medium text-slate-700">Titre du dossier</label>
            <input id="title" name="title" value="{{ old('title') }}" required maxlength="160"
                   class="mt-1 w-full rounded-lg border-slate-200 text-sm"
                   placeholder="Ex. Dossier digitalisation carte commercant">
            @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-slate-700">Description visible sur telephone</label>
            <textarea id="description" name="description" rows="4"
                      class="mt-1 w-full rounded-lg border-slate-200 text-sm"
                      placeholder="Quelques mots pour expliquer le contenu du dossier.">{{ old('description') }}</textarea>
            @error('description') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="expires_at" class="block text-sm font-medium text-slate-700">Expiration optionnelle</label>
            <input id="expires_at" name="expires_at" type="datetime-local" value="{{ old('expires_at') }}"
                   class="mt-1 rounded-lg border-slate-200 text-sm">
            @error('expires_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="files" class="block text-sm font-medium text-slate-700">Fichiers</label>
            <input id="files" name="files[]" type="file" multiple required
                   accept=".pdf,image/*,video/mp4,video/quicktime,video/webm,video/x-msvideo"
                   class="mt-1 block w-full rounded-lg border border-slate-200 p-3 text-sm">
            <p class="mt-2 text-xs text-slate-500">Formats acceptes: PDF, images JPG/PNG/WebP/GIF, videos MP4/MOV/AVI/WebM. Les limites exactes dependent aussi de PHP/Nginx sur le serveur.</p>
            @error('files') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            @error('files.*') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Creer le dossier et le QR
            </button>
            <a href="{{ route('admin.document-shares.index') }}" class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm hover:bg-slate-50">
                Annuler
            </a>
        </div>
    </form>
@endsection
