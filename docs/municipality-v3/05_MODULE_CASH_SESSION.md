# 5. CashSession Module

## 5.1 Mission

Gérer la **caisse physique** de l'agent municipal : ouverture matinale, suivi encaissements espèces, clôture avec comptage et validation superviseur.

## 5.2 Modèle conceptuel

```mermaid
flowchart LR
    OPEN[Ouverture caisse] --> COLLECT[Encaissements espèces]
    COLLECT --> CLOSE[Clôture agent]
    CLOSE --> APPROVE[Validation superviseur]
    APPROVE --> ARCHIVE[Session archivée]
```

**Mobile Money** : hors flux caisse espèces (pas d'impact `expected_cash`), mais rattaché à l'agent via `collected_by`.

## 5.3 États session

| Status | Description |
|--------|-------------|
| `open` | Encaissements espèces autorisés |
| `pending_close` | Agent a soumis comptage, attend validation |
| `closed` | Clôturée, écart calculé |
| `approved` | Superviseur a validé (ou auto si écart = 0 et < seuil) |

## 5.4 Composants

```
CashSessionService
├── SessionNumberGenerator      # OWE-CS-YYYYMMDD-USER-NN
├── ExpectedCashCalculator      # opening_float + SUM(cash payments)
├── VarianceAnalyzer            # écart, alertes
└── CashSessionPolicy           # 1 session open / agent
```

## 5.5 Ouverture caisse

### Prérequis
- Aucune session `open` pour l'agent
- Permission `municipal.cash_session.open`
- GPS enregistré (optionnel V3.0, obligatoire V3.1)

### API `POST /cash-sessions/open`

```json
{
  "opening_float": 50000,
  "device_id": "android-abc123",
  "gps": { "latitude": 0.65, "longitude": 9.34 }
}
```

### Offline
Session créée localement avec `local_session_id` ; sync à la reconnexion. Encaissements espèces offline rattachés à `local_cash_session_id` puis remappés.

## 5.6 Suivi temps réel

Pendant session `open` :

```
expected_cash = opening_float + SUM(
  municipal_payments.amount
  WHERE method = cash
    AND status = completed
    AND cash_session_id = session.id
)
```

Exposé via `GET /cash-sessions/current` pour l'agent connecté.

## 5.7 Clôture

Voir document 15 (détail). Résumé :

1. Agent saisit `counted_cash` (+ détail coupures V3.1)
2. `variance = counted_cash - expected_cash`
3. Si `|variance| > seuil` → `pending_close` + notification superviseur
4. Sinon → `closed` auto

## 5.8 Plafonds et alertes

| Config | Défaut | Action |
|--------|--------|--------|
| `max_cash_per_session` | 2 000 000 XAF | Bloquer nouvel encaissement espèces |
| `variance_auto_approve_max` | 500 XAF | Clôture auto |
| `variance_alert_threshold` | 5 000 XAF | Alerte superviseur |

## 5.9 API REST

| Méthode | Route | Description |
|---------|-------|-------------|
| POST | `/cash-sessions/open` | Ouvrir |
| GET | `/cash-sessions/current` | Session active agent |
| GET | `/cash-sessions/{id}` | Détail + paiements |
| POST | `/cash-sessions/{id}/close` | Clôture |
| POST | `/cash-sessions/{id}/approve` | Validation superviseur |
| GET | `/cash-sessions` | Liste (superviseur, filtres date) |

## 5.10 Intégration FiscalCollection

- `method=cash` **exige** `cash_session_id` valide et `open`
- `method=airtel_money|moov_money` : `cash_session_id` null

## 5.11 Audit

Chaque transition d'état → `audit_logs` avec `old_status`, `new_status`, montants, GPS.
