@php
    $payment = $receipt->payment;
    $operator = $payment?->operator;
    $agent = $payment?->agent;
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 4mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; width: 58mm; margin: 0; }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .line { border-top: 1px dashed #000; margin: 4px 0; }
        .hash { font-size: 6px; word-break: break-all; }
    </style>
</head>
<body>
    <div class="center bold">COMMUNE D'OWENDO</div>
    <div class="center">Quittance municipale</div>
    <div class="line"></div>
    <div>{{ $receipt->receipt_number }}</div>
    <div>{{ $receipt->generated_at?->format('d/m/Y H:i') }}</div>
    <div class="line"></div>
    <div class="bold">{{ $operator?->commercial_name }}</div>
    <div>{{ $operator?->public_id }}</div>
    <div>{{ $operator?->sector?->name ?? $operator?->secteur ?? '' }}</div>
    <div class="line"></div>
    <div class="bold">TOTAL: {{ number_format((float) $payment?->amount, 0, ',', ' ') }} XAF</div>
    <div>Agent: {{ $agent?->name }}</div>
    <div class="line"></div>
    <div class="hash">SIG: {{ substr($receipt->document_hash ?? '', 0, 16) }}...</div>
    <div class="center" style="font-size:6px;">{{ $receipt->receipt_qr_value }}</div>
</body>
</html>
