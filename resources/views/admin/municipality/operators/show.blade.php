@extends('layouts.admin')

@section('title', $operator->public_id)
@section('page_title', $operator->commercial_name)
@section('page_subtitle', $operator->public_id.' — Fiche opérateur économique')
@section('admin_page', 'municipality-operators')

@section('content')
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.municipality.operators.index') }}" class="text-sm text-sky-600 hover:underline">← Retour à la liste</a>
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.municipality.operators.qr.png', $operator) }}"
           class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Télécharger QR PNG</a>
        <a href="{{ route('admin.municipality.operators.qr.pdf', $operator) }}"
           class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Télécharger QR PDF</a>
        <a href="{{ route('admin.municipality.operators.qr.business-card', $operator) }}"
           class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-medium text-white hover:bg-slate-800">Imprimer carte commerce</a>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-base font-semibold text-slate-900">Informations générales</h2>
                <dl class="grid gap-3 sm:grid-cols-2 text-sm">
                    <div><dt class="text-slate-500">Identifiant</dt><dd class="font-mono font-medium">{{ $operator->public_id }}</dd></div>
                    <div><dt class="text-slate-500">Nom commercial</dt><dd class="font-medium">{{ $operator->commercial_name }}</dd></div>
                    <div><dt class="text-slate-500">Activité</dt><dd>{{ $operator->activity_label }}</dd></div>
                    <div><dt class="text-slate-500">Catégorie</dt><dd>{{ $operator->category?->name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Responsable</dt><dd>{{ $operator->responsible_name }}</dd></div>
                    <div><dt class="text-slate-500">Téléphone</dt><dd>{{ $operator->phone }}</dd></div>
                    <div><dt class="text-slate-500">Email</dt><dd>{{ $operator->email ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Zone</dt><dd>{{ $operator->sector?->name ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Statut fiscal</dt><dd>{{ $operator->current_tax_status?->label() ?? '—' }}</dd></div>
                    <div><dt class="text-slate-500">Enregistré le</dt><dd>{{ $operator->created_at?->format('d/m/Y H:i') }}</dd></div>
                    <div><dt class="text-slate-500">Agent</dt><dd>{{ $operator->registeredBy?->name ?? '—' }}</dd></div>
                </dl>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-base font-semibold text-slate-900">Géolocalisation</h2>
                <dl class="mb-4 grid gap-3 sm:grid-cols-2 text-sm">
                    <div><dt class="text-slate-500">Latitude</dt><dd class="font-mono">{{ $operator->latitude }}</dd></div>
                    <div><dt class="text-slate-500">Longitude</dt><dd class="font-mono">{{ $operator->longitude }}</dd></div>
                    <div><dt class="text-slate-500">Précision GPS</dt><dd>{{ $operator->gps_accuracy_m ?? '—' }} m</dd></div>
                </dl>
                <div class="overflow-hidden rounded-lg border border-slate-200">
                    <iframe title="Carte"
                            class="h-64 w-full"
                            loading="lazy"
                            src="https://www.openstreetmap.org/export/embed.html?bbox={{ $operator->longitude - 0.01 }}%2C{{ $operator->latitude - 0.01 }}%2C{{ $operator->longitude + 0.01 }}%2C{{ $operator->latitude + 0.01 }}&amp;layer=mapnik&amp;marker={{ $operator->latitude }}%2C{{ $operator->longitude }}"></iframe>
                </div>
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-base font-semibold text-slate-900">Fiscalité</h2>
                <div class="mb-4 grid gap-3 sm:grid-cols-3 text-sm">
                    <div class="rounded-lg bg-slate-50 p-3"><p class="text-slate-500">Solde dû</p><p class="text-lg font-semibold">{{ number_format($fiscal['balance_remaining'], 0, ',', ' ') }} XAF</p></div>
                    <div class="rounded-lg bg-slate-50 p-3"><p class="text-slate-500">Payé</p><p class="text-lg font-semibold">{{ number_format($fiscal['amount_paid'], 0, ',', ' ') }} XAF</p></div>
                    <div class="rounded-lg bg-slate-50 p-3"><p class="text-slate-500">Encaissements</p><p class="text-lg font-semibold">{{ number_format($fiscal['payments_total'], 0, ',', ' ') }} XAF</p></div>
                </div>

                <h3 class="mb-2 text-sm font-semibold text-slate-800">Taxes affectées</h3>
                <ul class="mb-4 divide-y divide-slate-100 text-sm">
                    @forelse ($fiscal['assignments'] as $assignment)
                        <li class="py-2">{{ $assignment->taxType?->name ?? '—' }} <span class="text-slate-500">({{ $assignment->taxType?->code }})</span></li>
                    @empty
                        <li class="py-2 text-slate-500">Aucune taxe affectée.</li>
                    @endforelse
                </ul>

                <h3 class="mb-2 text-sm font-semibold text-slate-800">Obligations ouvertes</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="text-left text-xs uppercase text-slate-500">
                            <tr>
                                <th class="py-2 pr-4">Référence</th>
                                <th class="py-2 pr-4">Taxe</th>
                                <th class="py-2 pr-4">Solde</th>
                                <th class="py-2">Échéance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($fiscal['obligations'] as $obligation)
                                <tr class="border-t border-slate-100">
                                    <td class="py-2 pr-4 font-mono text-xs">{{ $obligation->reference }}</td>
                                    <td class="py-2 pr-4">{{ $obligation->taxType?->name ?? '—' }}</td>
                                    <td class="py-2 pr-4">{{ number_format((float) $obligation->balance_due, 0, ',', ' ') }} XAF</td>
                                    <td class="py-2">{{ $obligation->due_date?->format('d/m/Y') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-4 text-slate-500">Aucune obligation ouverte.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <h3 class="mb-2 mt-4 text-sm font-semibold text-slate-800">Derniers paiements</h3>
                <ul class="divide-y divide-slate-100 text-sm">
                    @forelse ($operator->municipalPayments as $payment)
                        <li class="py-2 flex justify-between gap-4">
                            <span>{{ $payment->collected_at?->format('d/m/Y H:i') ?? '—' }}</span>
                            <span class="font-medium">{{ number_format((float) $payment->amount, 0, ',', ' ') }} XAF</span>
                        </li>
                    @empty
                        <li class="py-2 text-slate-500">Aucun paiement enregistré.</li>
                    @endforelse
                </ul>
            </section>
        </div>

        <div class="space-y-6">
            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-base font-semibold text-slate-900">QR Code</h2>
                @if ($operator->activeQrcode)
                    <p class="mb-3 font-mono text-xs text-slate-600">{{ $operator->public_id }}</p>
                    <img src="{{ route('admin.municipality.operators.qr.png', $operator) }}"
                         alt="QR {{ $operator->public_id }}"
                         class="mx-auto h-48 w-48 border border-slate-200 bg-white p-2">
                    <p class="mt-3 break-all text-xs text-slate-500">UUID scan : {{ $operator->activeQrcode->qr_uuid }}</p>
                @else
                    <p class="text-sm text-slate-500">Aucun QR actif. Téléchargez un QR pour le générer.</p>
                @endif
            </section>

            <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="mb-4 text-base font-semibold text-slate-900">Documents</h2>
                <ul class="space-y-3 text-sm">
                    @php
                        $photos = $operator->attachments->keyBy('purpose');
                        $docLabels = [
                            'facade' => 'Façade',
                            'trade_registry' => 'RCCM',
                            'business_license' => 'Patente',
                            'municipal_authorization' => 'Autorisation',
                        ];
                    @endphp
                    @foreach ($docLabels as $purpose => $label)
                        @php $attachment = $photos->get($purpose); @endphp
                        <li class="flex items-center justify-between gap-3 rounded-lg border border-slate-100 px-3 py-2">
                            <span>{{ $label }}</span>
                            @if ($attachment && $attachment->disk === 'public')
                                <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($attachment->path) }}"
                                   target="_blank" class="text-sky-600 hover:underline">Voir</a>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>
    </div>
@endsection
