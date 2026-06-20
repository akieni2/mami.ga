@extends('layouts.admin')

@section('title', 'Opérateurs économiques')
@section('page_title', 'Opérateurs économiques')
@section('page_subtitle', 'Registre communal — gestion industrielle')
@section('admin_page', 'municipality-operators')

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.municipality.operators.export.csv', request()->query()) }}"
               class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Exporter CSV</a>
            <a href="{{ route('admin.municipality.operators.export.excel', request()->query()) }}"
               class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Exporter Excel</a>
            <a href="{{ route('admin.municipality.operators.export.pdf', request()->query()) }}"
               class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Exporter PDF</a>
        </div>
        <a href="{{ route('admin.municipality.operators.qr-batch') }}"
           class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">QR par lot</a>
    </div>

    <form method="GET" id="operators-filter-form" class="mb-4 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 md:grid-cols-4 xl:grid-cols-7">
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Recherche rapide</label>
            <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Identifiant, commerce…"
                   class="w-full rounded-lg border-slate-200 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Identifiant</label>
            <input type="text" name="public_id" value="{{ $filters['public_id'] ?? '' }}" class="w-full rounded-lg border-slate-200 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Commerce</label>
            <input type="text" name="commercial_name" value="{{ $filters['commercial_name'] ?? '' }}" class="w-full rounded-lg border-slate-200 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Responsable</label>
            <input type="text" name="responsible_name" value="{{ $filters['responsible_name'] ?? '' }}" class="w-full rounded-lg border-slate-200 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Téléphone</label>
            <input type="text" name="phone" value="{{ $filters['phone'] ?? '' }}" class="w-full rounded-lg border-slate-200 text-sm">
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Zone</label>
            <select name="sector_id" class="w-full rounded-lg border-slate-200 text-sm">
                <option value="">Toutes</option>
                @foreach ($sectors as $sector)
                    <option value="{{ $sector->id }}" @selected((string) ($filters['sector_id'] ?? '') === (string) $sector->id)>{{ $sector->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="mb-1 block text-xs font-medium text-slate-500">Catégorie</label>
            <select name="category_id" class="w-full rounded-lg border-slate-200 text-sm">
                <option value="">Toutes</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Identifiant</th>
                        <th class="px-5 py-3">Commerce</th>
                        <th class="px-5 py-3">Responsable</th>
                        <th class="px-5 py-3">Téléphone</th>
                        <th class="px-5 py-3">Catégorie</th>
                        <th class="px-5 py-3">Zone</th>
                        <th class="px-5 py-3">Date création</th>
                        <th class="px-5 py-3">Statut</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($operators as $operator)
                        <tr>
                            <td class="px-5 py-3 font-mono text-xs">{{ $operator->public_id }}</td>
                            <td class="px-5 py-3 font-medium">{{ $operator->commercial_name }}</td>
                            <td class="px-5 py-3">{{ $operator->responsible_name }}</td>
                            <td class="px-5 py-3">{{ $operator->phone }}</td>
                            <td class="px-5 py-3">{{ $operator->category?->name ?? '—' }}</td>
                            <td class="px-5 py-3">{{ $operator->sector?->name ?? '—' }}</td>
                            <td class="px-5 py-3 text-slate-500">{{ $operator->created_at?->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-3">
                                <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $operator->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $operator->is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-right">
                                <a href="{{ route('admin.municipality.operators.show', $operator) }}" class="text-sky-600 hover:underline">Fiche</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-10 text-center text-slate-500">Aucun opérateur économique.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($operators->hasPages())
            <div class="border-t border-slate-100 px-5 py-3">{{ $operators->links() }}</div>
        @endif
    </div>

    <p class="mt-3 text-xs text-slate-500">{{ $operators->total() }} opérateur(s) — {{ $operators->perPage() }} par page</p>

    @push('scripts')
        <script>
            (function () {
                const form = document.getElementById('operators-filter-form');
                if (!form) return;
                let timer;
                form.querySelectorAll('input, select').forEach(function (el) {
                    el.addEventListener('input', function () {
                        clearTimeout(timer);
                        timer = setTimeout(function () { form.submit(); }, 350);
                    });
                    el.addEventListener('change', function () {
                        clearTimeout(timer);
                        timer = setTimeout(function () { form.submit(); }, 150);
                    });
                });
            })();
        </script>
    @endpush
@endsection
