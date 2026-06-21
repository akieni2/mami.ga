# Sprint 3.3 — Situation fiscale détaillée et encaissement par créance

## Objectif

Remplacer la saisie libre du montant par un encaissement basé sur les créances réelles du commerce, avec situation fiscale structurée (taxes, pénalités, amendes).

## Workflow cible

```
Scan QR → Situation fiscale → Sélection créances → Montant auto-calculé → Encaissement → Quittance
```

## Backend

### Migration

`2026_06_27_100000_add_obligation_type_to_fiscal_obligations.php`

- Colonne `obligation_type` : `tax` | `penalty` | `fine` (défaut `tax`)

### `OperatorFiscalSummaryService`

| Méthode | Usage |
|---------|--------|
| `build()` | Rétrocompatibilité — endpoint historique |
| `buildDetailed()` | Sprint 3.3 — créances groupées + historique |

Génération automatique des obligations courantes si taxes affectées (`ensureForOperator`).

### API

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/api/municipality/operators/{id}/fiscal-summary` | Situation détaillée |
| GET | `/api/municipality/fiscal/operator/{id}/summary` | **Conservé** (legacy) |
| POST | `/api/municipality/fiscal/collections` | `obligation_ids[]` **ou** `amount_xaf` |

Réponse `fiscal-summary` :

```json
{
  "operator": { "id": 5, "public_id": "OWE-COM-...", "commercial_name": "PANET" },
  "taxes": [{ "id": 1, "label": "Taxe — ...", "balance_due": "15000.00" }],
  "penalties": [],
  "fines": [],
  "total_due": "15000.00",
  "total_paid": "0.00",
  "remaining_balance": "15000.00",
  "payment_history": []
}
```

### Encaissement par créances

`ObligationAllocationService::allocateSelected()` :

- Valide les IDs sélectionnés (commerce, statut ouvert/partiel)
- Calcule le montant = somme des `balance_due`
- Refuse si `amount_xaf` fourni en même temps que `obligation_ids`

## Flutter

| Fichier | Changement |
|---------|------------|
| `fiscal_summary_screen.dart` | Taxes / pénalités / amendes, checkboxes, solde, historique |
| `collect_cash_screen.dart` | Mode sélection (`obligationIds`) — montant calculé |
| `fiscal_collection_repository.dart` | `FiscalDetailedSummary`, `fetchDetailedFiscalSummary()` |
| `app_router.dart` | Query `obligationIds=1,2` |

## Tests

```bash
php artisan test --filter=OperatorFiscalSummaryTest
php artisan test --filter=FiscalCollectionTest
```

## Périmètre non modifié

- Workflow QR (scan → lookup → navigation)
- Ouverture / fermeture caisse
- Émission quittances
- Endpoint legacy `/fiscal/operator/{id}/summary`

## Déploiement

```bash
php artisan migrate
cd mobile/mami_client && flutter build apk --release --dart-define=API_BASE_URL=https://api.mami.ga/api
```

## Note terrain PANET

Une taxe doit être **affectée** au commerce pour que des créances apparaissent. Sans affectation : message explicite à l'encaissement (Sprint 3.2.7).
