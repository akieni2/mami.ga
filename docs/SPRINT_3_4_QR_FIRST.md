# Sprint 3.4 — Workflow QR First

## Objectif

Supprimer la dépendance à la saisie manuelle de l'ID opérateur dans Situation fiscale et Encaissement. Le QR commerce devient le point d'entrée principal du recouvrement terrain.

## Sprint 3.4.1 — QR First (Situation fiscale + Encaissement)

### Situation fiscale

- Écran d'entrée : bouton **Scanner QR commerce** + séparateur **OU** + saisie manuelle ID (fallback).
- Scan caméra → lookup commerce → navigation automatique vers `FiscalSummaryScreen(operatorId)`.

### Encaissement

- Route `/municipality/recovery/collect` sans paramètres : écran QR-only (plus de champs ID / montant manuel).
- Workflow : scan → situation fiscale → sélection des créances → montant calculé → confirmation encaissement.

### Fichiers Flutter

- `presentation/widgets/qr_commerce_entry.dart` — widget partagé
- `fiscal_summary_screen.dart`, `collect_cash_screen.dart`, `scan_qr_camera_screen.dart` (redirect `field-control` | `fiscal-summary`)

## Sprint 3.4.2 — Historique des paiements

### API

`GET /api/municipality/operators/{id}/fiscal-summary` — section `payment_history` enrichie :

| Champ | Description |
|-------|-------------|
| `collected_at` | Date |
| `receipt_number` | Référence quittance |
| `amount_xaf` | Montant |
| `tax_concerned` | Taxe(s) via allocations |
| `agent_name` | Agent |
| `payment_method_label` | Mode paiement |

### UI

Section **Historique des paiements** sous les créances dans `FiscalSummaryScreen`.

Accueil agent : tuile **Historique des paiements** → mes encaissements.

## Sprint 3.4.3 — Contrôles terrain + Synchronisation

### Contrôles terrain

- Route `/municipality/field-control` → scan QR → fiche commerce → type de contrôle → `POST /operators/{id}/field-visits`
- Types : `presence_control`, `license_control`, `patent_control`, `municipal_control`

### Synchronisation

- Route `/municipality/sync`
- API `GET /api/municipality/sync/status` : compteurs commerces / paiements / quittances, état API
- Dernière synchro persistée localement (`SharedPreferences`) — préparation mode offline V2.1

## Déploiement VPS

```bash
git pull
composer install --no-dev
php artisan migrate --force
php artisan config:cache
```

Rebuild APK mobile obligatoire (pas de déploiement git côté Flutter sur le VPS).

## Tests

- `OperatorFiscalSummaryTest` — champs historique paiements
- `FieldVisitTest` — types contrôle terrain
- `MunicipalSyncStatusTest` — endpoint sync
