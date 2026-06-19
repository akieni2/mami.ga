@extends('layouts.admin')

@section('title', 'Fiscalité — Types de taxes')
@section('page_title', 'Fiscalité')
@section('page_subtitle', 'Types de taxes municipales')

@section('content')
    @include('admin.municipality.fiscal.partials.nav', ['active' => 'tax-types'])

    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="mb-1 text-sm font-semibold text-slate-700">Nouveau type de taxe</h2>
        <p class="mb-3 text-xs text-slate-500">Le code est un identifiant technique (ex. <span class="font-mono">TAX-COMMERCE</span>) : lettres, chiffres et tirets uniquement, sans espaces.</p>
        <form method="POST" action="{{ route('admin.municipality.fiscal.tax-types.store') }}" class="grid gap-3 md:grid-cols-4">
            @csrf
            <div>
                <input type="text" name="code" placeholder="TAX-COMMERCE" required
                       class="w-full rounded-lg border-slate-200 text-sm @error('code') border-rose-400 @enderror"
                       value="{{ old('code') }}">
                @include('admin.partials.field-error', ['field' => 'code'])
            </div>
            <div>
                <input type="text" name="name" placeholder="Nom" required
                       class="w-full rounded-lg border-slate-200 text-sm @error('name') border-rose-400 @enderror"
                       value="{{ old('name') }}">
                @include('admin.partials.field-error', ['field' => 'name'])
            </div>
            <div class="md:col-span-2">
                <input type="text" name="description" placeholder="Description"
                       class="w-full rounded-lg border-slate-200 text-sm @error('description') border-rose-400 @enderror"
                       value="{{ old('description') }}">
                @include('admin.partials.field-error', ['field' => 'description'])
            </div>
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white md:col-span-4 md:w-auto">Créer</button>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-5 py-3">Code</th>
                    <th class="px-5 py-3">Nom</th>
                    <th class="px-5 py-3">Statut</th>
                    <th class="px-5 py-3">Taux actif</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($taxTypes as $taxType)
                    <tr>
                        <td class="px-5 py-3 font-mono text-xs">{{ $taxType->code }}</td>
                        <td class="px-5 py-3">{{ $taxType->name }}</td>
                        <td class="px-5 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $taxType->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $taxType->is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </td>
                        <td class="px-5 py-3">{{ $taxType->activeRate ? number_format($taxType->activeRate->amount_xaf, 0, ',', ' ') . ' XAF' : '—' }}</td>
                        <td class="px-5 py-3 text-right">
                            <form method="POST" action="{{ route('admin.municipality.fiscal.tax-types.toggle', $taxType) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-sky-600 hover:underline">{{ $taxType->is_active ? 'Désactiver' : 'Activer' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">Aucun type de taxe.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-slate-100 px-5 py-3">{{ $taxTypes->links() }}</div>
    </div>
@endsection
