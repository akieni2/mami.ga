@extends('layouts.admin')

@section('title', 'Fiscalité — Affectations')
@section('page_title', 'Fiscalité')
@section('page_subtitle', 'Affectation taxes aux opérateurs')

@section('content')
    @include('admin.municipality.fiscal.partials.nav', ['active' => 'assignments'])

    @if ($operators->isEmpty())
        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Aucun opérateur économique actif en base. Enrôlez d'abord des opérateurs avant d'affecter des taxes.
        </div>
    @endif

    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4">
        <form method="GET" action="{{ route('admin.municipality.fiscal.assignments') }}" class="mb-4 flex flex-wrap gap-2">
            <input
                type="search"
                name="operator_search"
                value="{{ $filters['operator_search'] ?? '' }}"
                placeholder="Rechercher un commerce (nom ou OWE-COM-…)"
                class="min-w-[16rem] flex-1 rounded-lg border-slate-200 text-sm"
            >
            <button type="submit" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Rechercher</button>
        </form>
        <p class="mb-3 text-xs text-slate-500">
            Ordre alphabétique — {{ $operators->count() }} commerce(s) actif(s) affiché(s).
            Chaque taxe doit avoir un <strong>barème actif</strong> avant affectation.
        </p>
        <form method="POST" action="{{ route('admin.municipality.fiscal.assignments.store') }}" class="grid gap-3 md:grid-cols-3">
            @csrf
            <div>
                <select name="operator_id" required class="w-full rounded-lg border-slate-200 text-sm @error('operator_id') border-rose-400 @enderror">
                    <option value="">Opérateur</option>
                    @foreach ($operators as $operator)
                        <option value="{{ $operator->id }}" @selected(old('operator_id') == $operator->id)>{{ $operator->public_id }} — {{ $operator->commercial_name }}</option>
                    @endforeach
                </select>
                @include('admin.partials.field-error', ['field' => 'operator_id'])
            </div>
            <div>
                <select name="tax_type_id" required class="w-full rounded-lg border-slate-200 text-sm @error('tax_type_id') border-rose-400 @enderror">
                    <option value="">Taxe</option>
                    @foreach ($taxTypes as $type)
                        <option value="{{ $type->id }}" @selected(old('tax_type_id') == $type->id)>{{ $type->code }}</option>
                    @endforeach
                </select>
                @include('admin.partials.field-error', ['field' => 'tax_type_id'])
            </div>
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">Affecter</button>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr><th class="px-5 py-3">Opérateur</th><th class="px-5 py-3">Taxe</th><th class="px-5 py-3">Date</th><th class="px-5 py-3">Statut</th><th class="px-5 py-3"></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($assignments as $assignment)
                    <tr>
                        <td class="px-5 py-3">{{ $assignment->operator?->public_id }} — {{ $assignment->operator?->commercial_name }}</td>
                        <td class="px-5 py-3 font-mono text-xs">{{ $assignment->taxType?->code }}</td>
                        <td class="px-5 py-3">{{ $assignment->assigned_at?->format('d/m/Y') }}</td>
                        <td class="px-5 py-3">{{ $assignment->is_active ? 'Actif' : 'Inactif' }}</td>
                        <td class="px-5 py-3 text-right">
                            <form method="POST" action="{{ route('admin.municipality.fiscal.assignments.toggle', $assignment) }}" class="inline">@csrf<button class="text-sky-600 hover:underline">{{ $assignment->is_active ? 'Désactiver' : 'Activer' }}</button></form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">Aucune affectation.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-slate-100 px-5 py-3">{{ $assignments->links() }}</div>
    </div>
@endsection
