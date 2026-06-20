<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 24mm; }
        body { font-family: DejaVu Sans, sans-serif; margin: 0; }
        .page { page-break-after: always; text-align: center; padding-top: 40mm; }
        .page:last-child { page-break-after: auto; }
        .id { font-family: monospace; font-size: 18px; font-weight: bold; margin-bottom: 8px; }
        .commerce { font-size: 16px; margin-bottom: 4px; }
        .resp { font-size: 12px; color: #475569; margin-bottom: 20px; }
        img { width: 240px; height: 240px; }
        .missing { color: #b45309; font-size: 12px; margin-top: 20px; }
        .header { font-size: 10px; color: #94a3b8; margin-bottom: 24px; }
    </style>
</head>
<body>
    @foreach ($pages as $page)
        <div class="page">
            <p class="header">Lot QR {{ sprintf('%08d', $start) }}–{{ sprintf('%08d', $end) }} — {{ $generatedAt->format('d/m/Y H:i') }}</p>
            <p class="id">{{ $page['public_id'] }}</p>
            <p class="commerce">{{ $page['commercial_name'] }}</p>
            <p class="resp">{{ $page['responsible_name'] }}</p>
            @if ($page['png_base64'])
                <img src="data:image/png;base64,{{ $page['png_base64'] }}" alt="QR">
            @else
                <div class="missing">Commerce non enregistré — QR indisponible</div>
            @endif
        </div>
    @endforeach
</body>
</html>
