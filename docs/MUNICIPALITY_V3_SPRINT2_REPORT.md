# MAMI Municipality V3.0 — Sprint 2 Report

**Sprint :** Encaissement terrain & sessions de caisse  
**Date :** 18 juin 2026  
**Statut :** Validé — prêt pour push

---

## Objectif

Transformer l'application Android Agent Municipal en terminal de recouvrement terrain : sessions de caisse, consultation fiscale après scan QR, encaissement espèces avec allocation automatique FIFO, quittances `OWE-RCP-*`, traçabilité complète.

**Hors périmètre Sprint 2 :** Mobile Money, impression Bluetooth, PDF avancé (Sprint 3).

---

## Validation finale (18 juin 2026)

| Domaine | Statut | Détail |
|---------|--------|--------|
| Intégrité financière | ✅ | Session obligatoire, une session/agent, fermeture idempotente, commerce actif, obligations requises |
| Allocation FIFO | ✅ | Scénario A=5000, B=7000, C=8000, paiement 20000 → allocations exactes |
| Traçabilité | ✅ | `audit_logs` pour ouverture/fermeture caisse, scan, consultation, paiement, quittance |
| GPS | ✅ | Obligatoire agents terrain ; bypass superviseur (`municipal.payment.collect_without_gps`) si coordonnées absentes |
| QR sécurisé | ✅ | UUID et suffixe acceptés ; `OWE-COM-*` seul refusé |
| Quittances | ✅ | Émission auto `OWE-RCP-YYYY-NNNNNN` à chaque encaissement |
| Sessions caisse | ✅ | `OWE-CS-*`, `expected_amount_xaf` = fonds + Σ paiements |
| Dashboard superviseur | ✅ | Par agent, jour, quartier — requêtes agrégées, pas de N+1 |
| Sécurité | ✅ | Citoyen → 403 sur tous les endpoints recouvrement |
| Performance | ✅ | Dashboard < 30 requêtes / < 3 s avec 200 commerces et 500 paiements |
| Tests | ✅ | **134 tests Municipality verts** |
| Régression Taxi | ✅ | Tests ride échantillon verts |

---

## Backend livré

### Tables & migrations

- `cash_sessions`, `municipal_payment_allocations`
- Extensions `municipal_payments`, `field_visits`
- Index performance `(status, collected_at)`, `sector_id`

### Services

| Service | Rôle |
|---------|------|
| `CashSessionService` | Ouverture / fermeture, une session ouverte par agent |
| `FiscalCollectionService` | Encaissement, GPS, allocation, quittance |
| `ObligationAllocationService` | Allocation FIFO |
| `MunicipalReceiptEmissionService` | Quittances `OWE-RCP-*` |
| `FiscalSupervisorDashboardService` | KPIs agrégés |
| `OperatorFiscalSummaryService` | Consultation fiscale |
| `PaymentOrchestratorService` | Core `payments` + `transactions` |

### API (`/api/municipality/fiscal/`)

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/cash-sessions/current` | Session ouverte |
| POST | `/cash-sessions/open` | Ouvrir caisse |
| POST | `/cash-sessions/{id}/close` | Fermer caisse |
| GET | `/operator/{id}/summary` | Situation fiscale |
| POST | `/collections` | Encaissement espèces |
| GET | `/collections` | Mes encaissements |
| GET | `/supervisor/dashboard` | KPIs (agent, jour, quartier) |

### Permissions

- `municipal.cash_session.open` / `close`
- `municipal.payment.collect`
- `municipal.payment.collect_without_gps` (superviseur)
- `municipal.fiscal.view`

### Admin

- `/admin/municipality/collection` — tableau de bord superviseur

---

## Flutter Agent

Menu **Recouvrement** (`/municipality/recovery`) : ouverture caisse, scan QR, situation fiscale, encaissement, historique, fermeture caisse.

**Test manuel recommandé sur appareil :** Ouvrir caisse → Scanner QR → Consulter → Encaisser → Historique → Fermer caisse.

---

## Tests

| Fichier | Focus |
|---------|-------|
| `CashSessionTest` | Sessions de caisse |
| `FiscalCollectionTest` | Encaissement |
| `PaymentAllocationTest` | Allocation FIFO |
| `OperatorFiscalSummaryTest` | Consultation & dashboard |
| `Sprint2FinalValidationTest` | Validation intégrale pré-push |

```bash
php artisan test tests/Feature/Municipality
```

---

## Sprint 3 — prochaines étapes

- Impression Bluetooth 58 mm
- Quittances PDF officielles + signature numérique
- Vérification publique des quittances
- Airtel Money / Moov Money
- Mode offline complet
