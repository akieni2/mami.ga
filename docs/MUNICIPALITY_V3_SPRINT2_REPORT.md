# MAMI Municipality V3.0 — Sprint 2 Report

**Sprint :** Encaissement terrain & sessions de caisse  
**Date :** 18 juin 2026  
**Statut :** Livré (backend, API, admin, Flutter agent, tests)

---

## Objectif

Transformer l'application Android Agent Municipal en terminal de recouvrement terrain : sessions de caisse, consultation fiscale après scan QR, encaissement espèces avec allocation automatique FIFO, traçabilité complète (field_visits + audit_logs).

**Hors périmètre Sprint 2 :** Mobile Money, impression Bluetooth, quittance PDF avancée (prévus Sprint 3).

---

## Backend livré

### Migrations

- `cash_sessions` — référence `OWE-CS-YYYY-NNNNNN`, montants, statuts, GPS, device_id
- `municipal_payment_allocations` — lien paiement ↔ obligation
- Extensions `municipal_payments` : session, core_payment, GPS, idempotence `client_operation_id`
- Extensions `field_visits` : `cash_session_id`, `municipal_payment_id`, `operator_id` nullable (sessions)

### Services

| Service | Rôle |
|---------|------|
| `CashSessionService` | Ouverture / fermeture, une session ouverte par agent |
| `CashSessionReferenceGenerator` | Références `OWE-CS-*` |
| `OperatorFiscalSummaryService` | Consultation fiscale + visites scan/consultation |
| `ObligationAllocationService` | Allocation FIFO (due_date, id) |
| `FiscalCollectionService` | Orchestration encaissement + contrôles GPS |
| `PaymentOrchestratorService` | Création `payments` + `transactions` Core |

### API (`/api/municipality/fiscal/...`)

| Méthode | Route | Description |
|---------|-------|-------------|
| GET | `/cash-sessions/current` | Session ouverte de l'agent |
| POST | `/cash-sessions/open` | Ouvrir caisse |
| POST | `/cash-sessions/{id}/close` | Fermer caisse |
| GET | `/cash-sessions` | Liste (superviseur) |
| GET | `/operator/{id}/summary` | Situation fiscale commerce |
| POST | `/collections` | Encaissement espèces |
| GET | `/collections` | Mes encaissements |
| GET | `/supervisor/dashboard` | KPIs superviseur |

Scan QR existant : `GET /operators/by-qr/{value}` enregistre désormais une visite `scan` + audit.

### Permissions

- `municipal.cash_session.open` / `close`
- `municipal.payment.collect`
- `municipal.fiscal.view`

Attribuées au rôle `MunicipalAgent` et `Admin`.

### Contrôles obligatoires

- Session ouverte et appartenant à l'agent
- GPS présent, précision ≤ `mami.municipality_collection_max_gps_accuracy_m` (défaut 50 m)
- Commerce actif
- Montant ≤ solde dû (pas de surpaiement)

### Admin superviseur

- Route : `/admin/municipality/collection`
- Vue : sessions ouvertes, collecte du jour, par agent, par jour (14 jours)

---

## Flutter Agent

Menu **Recouvrement** activé depuis l'accueil agent (`/municipality/recovery`) :

- Ouvrir caisse
- Scanner QR commerce (saisie UUID)
- Situation fiscale
- Encaisser
- Mes encaissements
- Fermer caisse

Repository : `fiscal_collection_repository.dart`

---

## Tests

| Fichier | Tests |
|---------|-------|
| `CashSessionTest` | 12 |
| `FiscalCollectionTest` | 11 |
| `PaymentAllocationTest` | 10 |
| `OperatorFiscalSummaryTest` | 12 |

**Total Municipality :** 115 tests verts (45 nouveaux Sprint 2).  
**Régression Taxi :** non impactée (module isolé).

Commande :

```bash
php artisan test tests/Feature/Municipality
```

---

## Flux métier

```
Agent → Ouvrir caisse → Scanner QR → Consultation fiscale
     → Encaissement espèces → Allocation FIFO obligations
     → Fermer caisse
```

Chaque étape crée `field_visits` et `audit_logs`.

---

## Prochaines étapes (Sprint 3)

- Airtel Money / Moov Money
- Impression Bluetooth
- Quittance PDF avancée
- Scan caméra natif (actuellement saisie UUID)
