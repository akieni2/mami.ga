@php
    /** @var \App\Modules\Municipality\Models\MunicipalReceipt $receipt */
    $payment = $receipt->payment;
    $operator = $payment?->operator;
    $agent = $payment?->agent;
    $taxLines = $payment?->allocations ?? collect();
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; margin: 24px; }
        .header { text-align: center; border-bottom: 2px solid #0b3d2e; padding-bottom: 12px; margin-bottom: 16px; }
        .logo { font-size: 20px; font-weight: bold; color: #0b3d2e; }
        .subtitle { font-size: 12px; color: #555; }
        h1 { font-size: 16px; margin: 0 0 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; }
        .totals { margin-top: 16px; font-size: 14px; font-weight: bold; }
        .footer { margin-top: 24px; font-size: 9px; color: #666; border-top: 1px solid #ddd; padding-top: 8px; }
        .hash { font-family: monospace; font-size: 8px; word-break: break-all; }
        .qr { text-align: center; margin-top: 12px; font-size: 9px; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">COMMUNE D'OWENDO</div>
        <div class="subtitle">République Gabonaise — Mairie d'Owendo</div>
        <h1>QUITTANCE MUNICIPALE OFFICIELLE</h1>
        <div><strong>{{ $receipt->receipt_number }}</strong></div>
    </div>

    <table>
        <tr><th>Date d'émission</th><td>{{ $receipt->generated_at?->format('d/m/Y H:i') }}</td></tr>
        <tr><th>Commerce</th><td>{{ $operator?->commercial_name }}</td></tr>
        <tr><th>Référence commerce</th><td>{{ $operator?->public_id }}</td></tr>
        <tr><th>Quartier</th><td>{{ $operator?->sector?->name ?? $operator?->secteur ?? '—' }}</td></tr>
        <tr><th>Agent collecteur</th><td>{{ $agent?->name }}</td></tr>
        <tr><th>Période</th><td>{{ $payment?->payment_period ?? '—' }}</td></tr>
    </table>

    <table>
        <thead>
            <tr><th>Taxe</th><th>Période</th><th>Montant (XAF)</th></tr>
        </thead>
        <tbody>
            @forelse ($taxLines as $allocation)
                <tr>
                    <td>{{ $allocation->fiscalObligation?->taxType?->name ?? 'Taxe' }}</td>
                    <td>{{ $allocation->fiscalObligation?->period_start?->format('m/Y') ?? '—' }}</td>
                    <td>{{ number_format((float) $allocation->amount_allocated, 0, ',', ' ') }}</td>
                </tr>
            @empty
                <tr><td colspan="3">Encaissement global</td><td>{{ number_format((float) $payment?->amount, 0, ',', ' ') }}</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="totals">Montant total : {{ number_format((float) $payment?->amount, 0, ',', ' ') }} XAF</div>

    <div class="footer">
        <div>Signature numérique (SHA-256) :</div>
        <div class="hash">{{ $receipt->document_hash }}</div>
        <div class="qr">Vérification : {{ $receipt->receipt_qr_value }}</div>
    </div>
</body>
</html>
