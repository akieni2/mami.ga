<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; text-align: center; padding: 40px; }
        h1 { font-size: 22px; margin-bottom: 8px; }
        .id { font-family: monospace; font-size: 16px; color: #333; margin-bottom: 24px; }
        img { width: 280px; height: 280px; }
        .meta { margin-top: 24px; font-size: 11px; color: #666; }
    </style>
</head>
<body>
    <h1>{{ $operator->commercial_name }}</h1>
    <p class="id">{{ $operator->public_id }}</p>
    <img src="data:image/png;base64,{{ $pngBase64 }}" alt="QR">
    <p class="meta">Commune d'Owendo — MAMI.GA<br>Responsable : {{ $operator->responsible_name }}</p>
</body>
</html>
