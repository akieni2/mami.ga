@extends('layouts.admin')

@section('title', 'Fiscalité — Taux')
@section('page_title', 'Fiscalité')
@section('page_subtitle', 'Taux et périodicités')

@section('content')
    @include('admin.municipality.fiscal.partials.nav', ['active' => 'rates'])

    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4">
        <h2 class="mb-3 text-sm font-semibold text-slate-700">Nouveau taux (historique conservé)</h2>
        <form method="POST" action="{{ route('admin.municipality.fiscal.rates.store') }}" class="grid gap-3 md:grid-cols-3">
            @csrf
            <div>
                <select name="tax_type_id" required class="w-full rounded-lg border-slate-200 text-sm @error('tax_type_id') border-rose-400 @enderror">
                    <option value="">Type de taxe</option>
                    @foreach ($taxTypes as $type)
                        <option value="{{ $type->id }}" @selected(old('tax_type_id') == $type->id)>{{ $type->code }} — {{ $type->name }}</option>
                    @endforeach
                </select>
                @include('admin.partials.field-error', ['field' => 'tax_type_id'])
            </div>
            <div>
                <input type="number" name="amount_xaf" min="0" step="1" placeholder="Montant XAF" required
                       class="w-full rounded-lg border-slate-200 text-sm @error('amount_xaf') border-rose-400 @enderror"
                       value="{{ old('amount_xaf') }}">
                @include('admin.partials.field-error', ['field' => 'amount_xaf'])
            </div>
            <div>
                <select name="billing_period" required class="w-full rounded-lg border-slate-200 text-sm @error('billing_period') border-rose-400 @enderror">
                    @foreach ($billingPeriods as $period)
                        <option value="{{ $period->value }}" @selected(old('billing_period', $billingPeriods[0]->value ?? '') === $period->value)>{{ $period->label() }}</option>
                    @endforeach
                </select>
                @include('admin.partials.field-error', ['field' => 'billing_period'])
            </div>
            <div>
                <input type="date" name="valid_from" required
                       class="w-full rounded-lg border-slate-200 text-sm @error('valid_from') border-rose-400 @enderror"
                       value="{{ old('valid_from', now()->toDateString()) }}">
                @include('admin.partials.field-error', ['field' => 'valid_from'])
            </div>
            <div>
                <input type="date" name="valid_to" placeholder="Fin validité"
                       class="w-full rounded-lg border-slate-200 text-sm @error('valid_to') border-rose-400 @enderror"
                       value="{{ old('valid_to') }}">
                @include('admin.partials.field-error', ['field' => 'valid_to'])
            </div>
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white">Ajouter taux</button>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
            <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-5 py-3">Taxe</th>
                    <th class="px-5 py-3">Montant</th>
                    <th class="px-5 py-3">Périodicité</th>
                    <th class="px-5 py-3">Validité</th>
                    <th class="px-5 py-3">Statut</th>
                    <th class="px-5 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($rates as $rate)
                    <tr>
                        <td class="px-5 py-3 font-mono text-xs">{{ $rate->taxType?->code }}</td>
                        <td class="px-5 py-3">{{ number_format($rate->amount_xaf, 0, ',', ' ') }} XAF</td>
                        <td class="px-5 py-3">{{ $rate->billing_period->label() }}</td>
                        <td class="px-5 py-3">{{ $rate->valid_from->format('d/m/Y') }} — {{ $rate->valid_to?->format('d/m/Y') ?? '∞' }}</td>
                        <td class="px-5 py-3">{{ $rate->is_active ? 'Actif' : 'Inactif' }}</td>
                        <td class="px-5 py-3 text-right">
                            @if ($rate->is_active)
                                <form method="POST" action="{{ route('admin.municipality.fiscal.rates.deactivate', $rate) }}" class="inline">@csrf<button class="text-rose-600 hover:underline">Désactiver</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">Aucun taux.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="border-t border-slate-100 px-5 py-3">{{ $rates->links() }}</div>
    </div>
@endsection
