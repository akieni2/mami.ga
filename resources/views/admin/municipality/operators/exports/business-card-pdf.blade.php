<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; margin: 0; padding: 24px; }
        .card { border: 2px solid #0f172a; border-radius: 12px; padding: 24px; text-align: center; }
        h1 { font-size: 20px; margin: 0 0 6px; }
        .id { font-family: monospace; font-size: 15px; color: #334155; margin-bottom: 16px; }
        .name { font-size: 14px; margin-bottom: 16px; }
        img { width: 220px; height: 220px; }
        .footer { margin-top: 16px; font-size: 11px; color: #64748b; }
    </style>
</head>
<body>
    <div class="card">
        <h1>{{ $operator->commercial_name }}</h1>
        <p class="id">{{ $operator->public_id }}</p>
        <p class="name">Responsable : {{ $operator->responsible_name }}</p>
        <img src="data:image/png;base64,{{ $pngBase64 }}" alt="QR">
        <p class="footer">Commune d'Owendo — Carte commerce officielle MAMI.GA</p>
    </div>
</body>
</html>
