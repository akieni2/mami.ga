<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h1 { font-size: 16px; margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; font-size: 10px; text-transform: uppercase; }
        .meta { color: #666; font-size: 10px; margin-bottom: 12px; }
    </style>
</head>
<body>
    <h1>Registre des opérateurs économiques — Owendo</h1>
    <p class="meta">Généré le {{ $generatedAt->format('d/m/Y H:i') }} — {{ $operators->count() }} ligne(s) affichée(s) / {{ $total }} total</p>
    <table>
        <thead>
            <tr>
                <th>Identifiant</th>
                <th>Commerce</th>
                <th>Responsable</th>
                <th>Téléphone</th>
                <th>Catégorie</th>
                <th>Zone</th>
                <th>Création</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($operators as $operator)
                <tr>
                    <td>{{ $operator->public_id }}</td>
                    <td>{{ $operator->commercial_name }}</td>
                    <td>{{ $operator->responsible_name }}</td>
                    <td>{{ $operator->phone }}</td>
                    <td>{{ $operator->category?->name ?? '—' }}</td>
                    <td>{{ $operator->sector?->name ?? '—' }}</td>
                    <td>{{ $operator->created_at?->format('d/m/Y') }}</td>
                    <td>{{ $operator->is_active ? 'Actif' : 'Inactif' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
