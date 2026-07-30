@extends('layouts.admin')

@section('title', $documentShare->title)
@section('page_title', $documentShare->title)
@section('page_subtitle', 'Dossier public et QR code')

@section('content')
    <div class="grid gap-5 xl:grid-cols-[360px,1fr]">
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mx-auto flex max-w-xs justify-center rounded-lg border border-slate-200 bg-white p-3">
                <img src="{{ route('admin.document-shares.qr', $documentShare) }}" alt="QR code {{ $documentShare->title }}" class="h-64 w-64">
            </div>

            <div class="mt-4 space-y-3 text-sm">
                <div>
                    <div class="font-medium text-slate-700">Lien public</div>
                    <a href="{{ $publicUrl }}" target="_blank" class="break-all text-sky-700 hover:underline">{{ $publicUrl }}</a>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('admin.document-shares.qr', $documentShare) }}" target="_blank"
                       class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50">
                        Ouvrir le QR
                    </a>
                    <form method="POST" action="{{ route('admin.document-shares.toggle', $documentShare) }}">
                        @csrf
                        <button type="submit" class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50">
                            {{ $documentShare->is_active ? 'Desactiver' : 'Activer' }}
                        </button>
                    </form>
                </div>
                <form method="POST" action="{{ route('admin.document-shares.destroy', $documentShare) }}"
                      onsubmit="return confirm('Supprimer ce dossier public et ses fichiers ?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-lg border border-red-200 bg-white px-3 py-2 text-sm text-red-700 hover:bg-red-50">
                        Supprimer
                    </button>
                </form>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-5">
                <div class="grid gap-3 text-sm sm:grid-cols-3">
                    <div>
                        <div class="text-slate-500">Statut</div>
                        <div class="font-medium">{{ $documentShare->isPubliclyAvailable() ? 'Actif' : 'Inactif' }}</div>
                    </div>
                    <div>
                        <div class="text-slate-500">Scans</div>
                        <div class="font-medium">{{ $documentShare->scan_count }}</div>
                    </div>
                    <div>
                        <div class="text-slate-500">Expiration</div>
                        <div class="font-medium">{{ $documentShare->expires_at?->format('d/m/Y H:i') ?? 'Aucune' }}</div>
                    </div>
                </div>
                @if ($documentShare->description)
                    <p class="mt-4 text-sm text-slate-600">{{ $documentShare->description }}</p>
                @endif
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($documentShare->files as $file)
                    <div class="flex flex-wrap items-center justify-between gap-3 p-5">
                        <div>
                            <div class="font-medium text-slate-900">{{ $file->original_name }}</div>
                            <div class="mt-1 text-xs text-slate-500">{{ $file->mime_type ?? 'Fichier' }} · {{ $file->humanSize() }} · {{ $file->download_count }} telechargement(s)</div>
                        </div>
                        <a href="{{ route('document-shares.public.download', [$documentShare, $file]) }}"
                           class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm hover:bg-slate-50">
                            Tester le telechargement
                        </a>
                    </div>
                @empty
                    <div class="p-10 text-center text-sm text-slate-500">Aucun fichier.</div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
