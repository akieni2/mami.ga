# 16. Audit et traçabilité

## 16.1 Objectif

Garantir une **piste d'audit complète** pour chaque opération financière municipale : qui, quoi, quand, où (GPS), depuis quel terminal.

## 16.2 Sources de vérité

| Couche | Mécanisme |
|--------|-----------|
| **Métier** | Tables `municipal_payment_voids`, `municipal_refunds`, statuts immuables |
| **Core** | `audit_logs` polymorphique |
| **Infrastructure** | Logs Laravel structurés JSON |
| **Mobile** | `device_id`, horodatage local + serveur |

## 16.3 Événements audités

| Entité | Actions |
|--------|---------|
| `MunicipalPayment` | created, completed, voided, sync_failed |
| `MunicipalReceipt` | issued, printed, reprinted, voided |
| `CashSession` | opened, closed, approved, rejected |
| `MunicipalRefund` | requested, approved, completed |
| `FiscalObligation` | created (job), allocated, deallocated |
| `MunicipalTaxType` | created, updated, archived |
| `MunicipalTaxRate` | created, superseded |
| `OperatorTaxAssignment` | created, suspended, ended |
| `OfflineSyncBatch` | received, processed, rejected |

## 16.4 Schéma `audit_logs` (Core existant)

Chaque entrée :

```json
{
  "auditable_type": "App\\Modules\\Municipality\\Models\\MunicipalPayment",
  "auditable_id": 1001,
  "user_id": 12,
  "action": "completed",
  "old_values": { "status": "pending_sync" },
  "new_values": { "status": "completed" },
  "metadata": {
    "ip": "197.x.x.x",
    "user_agent": "MAMI-Android/3.0",
    "device_id": "android-abc",
    "gps": { "lat": 0.65, "lng": 9.34 },
    "client_operation_id": "uuid"
  },
  "created_at": "2026-06-16T14:32:00Z"
}
```

## 16.5 Chaîne de traçabilité encaissement

```
QR scan (qr_uuid)
  → operator_id
  → operator_tax_assignments (taxes actives)
  → fiscal_obligations (par taxe / période)
  → municipal_payment (collected_by, collected_at, gps)
  → municipal_payment_allocations
  → payment Core (payment_id)
  → transaction Core
  → municipal_receipt (receipt_number)
  → PDF path
  → print_count
```

Requête forensique unique par `receipt_number` ou `client_operation_id`.

## 16.6 Immutabilité

| Donnée | Politique |
|--------|-----------|
| `receipt_number` | Jamais modifié |
| Montant payment completed | Modifiable uniquement via void/refund |
| `audit_logs` | Append-only, pas de UPDATE/DELETE |
| PDF émis | Nouvelle version si re-génération, ancien conservé |

## 16.7 Horodatage

- Serveur : UTC en base
- Affichage : `Africa/Libreville`
- Mobile offline : `device_collected_at` + `server_received_at` — écart loggé si > 5 min

## 16.8 Accès audit

| Rôle | Accès |
|------|-------|
| `municipal_finance` | Recherche complète |
| `mayor` | Lecture rapports |
| `municipal_supervisor` | Son équipe |
| Agent | Ses propres opérations |

API : `GET /audit/municipal-payments?from=&to=&agent_id=`

## 16.9 Rétention

| Donnée | Durée |
|--------|-------|
| audit_logs | 10 ans (exigence comptable publique) |
| PDF quittances | 10 ans |
| Logs applicatifs | 90 jours |
| Sync batches | 1 an |

## 16.10 Conformité

- Alignement principes comptabilité publique locale
- Export périodique vers archive froide (V3.5)
- Hash chaîné logs (V4 optionnel blockchain-lite)

## 16.11 Monitoring sécurité

Alertes automatiques :
- Void > 3 / agent / jour
- Remboursement sans approbation tentative
- Accès audit massif anormal
- Sync batch rejeté répété même device

## 16.12 Tests audit

- Chaque action API financière crée ≥ 1 audit_log
- Void restaure obligation + log
- Réimpression incrémente print_count + log
