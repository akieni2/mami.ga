@extends('layouts.admin')

@section('title', 'QR par lot')
@section('page_title', 'Génération QR par lot')
@section('page_subtitle', 'Export PDF A4 imprimable')
@section('admin_page', 'municipality-operators')

@section('content')
    <div class="mb-4">
        <a href="{{ route('admin.municipality.operators.index') }}" class="text-sm text-sky-600 hover:underline">← Retour à la liste</a>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <form method="POST" action="{{ route('admin.municipality.operators.qr-batch.generate') }}"
              class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            @csrf
            <h2 class="mb-4 text-base font-semibold text-slate-900">Plage de séquence</h2>
            <p class="mb-4 text-sm text-slate-600">Génère un PDF A4 avec un QR par page pour chaque identifiant <span class="font-mono">OWE-COM-XXXXXXXX</span>.</p>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">Début</label>
                    <input type="number" name="start" min="1" max="99999999" value="{{ old('start', 1) }}" required
                           class="w-full rounded-lg border-slate-200 text-sm font-mono">
                    <p class="mt-1 text-xs text-slate-400">Ex. 1 → OWE-COM-00000001</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-slate-500">Fin</label>
                    <input type="number" name="end" min="1" max="99999999" value="{{ old('end', 100) }}" required
                           class="w-full rounded-lg border-slate-200 text-sm font-mono">
                    <p class="mt-1 text-xs text-slate-400">Ex. 100 → OWE-COM-00000100</p>
                </div>
            </div>

            @if ($errors->any())
                <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            <button type="submit" class="mt-6 rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">
                Générer le PDF
            </button>
        </form>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="mb-4 text-base font-semibold text-slate-900">Lots prédéfinis</h2>
            <p class="mb-4 text-sm text-slate-600">Cliquez pour préremplir le formulaire (à partir de 1).</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($presets as $size)
                    <button type="button"
                            class="preset-batch rounded-lg border border-slate-200 px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                            data-start="1" data-end="{{ $size }}">
                        {{ number_format($size, 0, ',', ' ') }} QR
                    </button>
                @endforeach
            </div>
            <p class="mt-4 text-xs text-slate-500">Maximum {{ number_format($maxBatch, 0, ',', ' ') }} QR par export.</p>
        </div>
    </div>

    @push('scripts')
        <script>
            document.querySelectorAll('.preset-batch').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    document.querySelector('input[name=start"]').value = btn.dataset.start;
                    document.querySelector('input[name=end"]').value = btn.dataset.end;
                });
            });
        </script>
    @endpush
@endsection
