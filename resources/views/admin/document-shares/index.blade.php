@extends('layouts.admin')

@section('title', 'Dossiers publics')
@section('page_title', 'Dossiers publics')
@section('page_subtitle', 'QR codes pour partager PDF, photos et videos')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-slate-600">Chaque dossier genere une page publique lisible sur telephone, avec telechargement fichier par fichier.</p>
        <a href="{{ route('admin.document-shares.create') }}"
           class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
            Creer un dossier
        </a>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-5 py-3">Titre</th>
                    <th class="px-5 py-3">Fichiers</th>
                    <th class="px-5 py-3">Scans</th>
                    <th class="px-5 py-3">Statut</th>
                    <th class="px-5 py-3">Expiration</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($shares as $share)
                    <tr>
                        <td class="px-5 py-3">
                            <div class="font-medium text-slate-900">{{ $share->title }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $share->creator?->name ?? 'Admin' }}</div>
                        </td>
                        <td class="px-5 py-3">{{ $share->files_count }}</td>
                        <td class="px-5 py-3">{{ $share->scan_count }}</td>
                        <td class="px-5 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $share->isPubliclyAvailable() ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                {{ $share->isPubliclyAvailable() ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>
                        <td class="px-5 py-3">{{ $share->expires_at?->format('d/m/Y H:i') ?? 'Aucune' }}</td>
                        <td class="px-5 py-3 text-right">
                            <a href="{{ route('admin.document-shares.show', $share) }}" class="text-sky-600 hover:underline">Detail</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-10 text-center text-slate-500">Aucun dossier public.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($shares->hasPages())
            <div class="border-t border-slate-100 px-5 py-4">{{ $shares->links() }}</div>
        @endif
    </div>
@endsection
