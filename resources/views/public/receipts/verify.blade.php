<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vérification quittance — Owendo</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 480px; margin: 40px auto; padding: 0 16px; }
        .card { border: 1px solid #ddd; border-radius: 8px; padding: 20px; }
        .valid { border-color: #16a34a; background: #f0fdf4; }
        .invalid { border-color: #dc2626; background: #fef2f2; }
        h1 { font-size: 1.25rem; margin-top: 0; }
        dl { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        dt { color: #666; }
        .hash { font-family: monospace; font-size: 11px; word-break: break-all; }
    </style>
</head>
<body>
    <div class="card {{ ($result['valid'] ?? false) ? 'valid' : 'invalid' }}">
        <h1>Vérification quittance municipale</h1>
        @if(($result['status'] ?? '') === 'not_found')
            <p>{{ $result['message'] }}</p>
        @else
            <p><strong>Statut :</strong> {{ $result['status_label'] ?? $result['status'] }}</p>
            <dl>
                <dt>Référence</dt><dd>{{ $result['receipt_number'] }}</dd>
                <dt>Date</dt><dd>{{ $result['issued_at'] }}</dd>
                <dt>Montant</dt><dd>{{ $result['amount_xaf'] }} XAF</dd>
                <dt>Commerce</dt><dd>{{ $result['operator']['commercial_name'] ?? '—' }}</dd>
                <dt>Réf. commerce</dt><dd>{{ $result['operator']['public_id'] ?? '—' }}</dd>
            </dl>
            <p class="hash">Signature : {{ $result['document_hash'] }}</p>
        @endif
    </div>
</body>
</html>
