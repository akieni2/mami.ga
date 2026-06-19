@extends('layouts.admin')

@section('title', 'Fiscalité — Objectifs')
@section('page_title', 'Fiscalité')
@section('page_subtitle', 'Objectifs annuels de recouvrement')

@section('content')
    @include('admin.municipality.fiscal.partials.nav', ['active' => 'targets'])

    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4">
        <form method="POST" action="{{ route('admin.municipality.fiscal.targets.store') }}" class="grid gap-3 md:grid-cols-4">
            @csrf
            <div>
                <select name="tax_type_id" required class="w-full rounded-lg border-slate-200 text-sm @error('tax_type_id') border-rose-400 @enderror">
                    <option value="">Type de taxe</option>
                    @foreach ($taxTypes as $type)
                        <option value="{{ $type->id }}" @selected(old('tax_type_id') == $type->id)>{{ $type->code }}</option>
                    @endforeach
                </select>
                @include('admin.partials.field-error', ['field' => 'tax_type_id'])
            </div>
            <div>
                <input type="number" name="fiscal_year" min="2000" max="2100" value="{{ old('fiscal_year', now()->year) }}" required
                       class="w-full rounded-lg border-slate-200 text-sm @error('fiscal_year') border-rose-400 @enderror">
                @include('admin.partials.field-error', ['field' => 'fiscal_year'])
            </div>
            <div>
                <input type="number" name="target_amount_xaf" min="0" step="1" placeholder="Objectif XAF" required
                       class="w-full rounded-lg border-slate-200 text-sm @error('target_amount_xaf') border-rose-400 @enderror"
                       value="{{ old('target_amount_xaf') }}">
                @include('admin.partials.field-error', ['field' => 'target_amount_xaf'])
            </div>
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">Enregistrer</button>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr><th class="px-5 py-3">Taxe</th><th class="px-5 py-3">Année</th><th class="px-5 py-3">Objectif</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($targets as $target)
                    <tr>
                        <td class="px-5 py-3 font-mono text-xs">{{ $target->taxType?->code }}</td>
                        <td class="px-5 py-3">{{ $target->fiscal_year }}</td>
                        <td class="px-5 py-3">{{ number_format($target->target_amount_xaf, 0, ',', ' ') }} XAF</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-5 py-8 text-center text-slate-500">Aucun objectif.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-slate-100 px-5 py-3">{{ $targets->links() }}</div>
    </div>
@endsection
