# 15. Gestion des clôtures de caisse

## 15.1 Mission

Clôturer proprement la journée de recouvrement espèces : comptage physique, calcul écart, validation superviseur, archivage.

## 15.2 Processus complet

```mermaid
flowchart TD
    A[Agent: Fin tournée] --> B[Écran clôture]
    B --> C[Saisie counted_cash]
    C --> D{Détail coupures?}
    D -->|V3.1+| E[cash_session_denominations]
    D -->|V3.0| F[Calcul variance]
    E --> F
    F --> G{|variance| ≤ seuil auto?}
    G -->|Oui| H[status = closed]
    G -->|Non| I[status = pending_close]
    I --> J[Notification superviseur]
    J --> K{Décision}
    K -->|Approuver| L[status = approved]
    K -->|Rejet + re-compte| B
    H --> M[Auto-approve si règle]
    L --> N[Archive + rapport]
    M --> N
```

## 15.3 Calculs

```
expected_cash = opening_float
  + SUM(cash payments completed in session)
  - SUM(cash voids in session)

variance = counted_cash - expected_cash
variance_pct = variance / expected_cash * 100  (si expected > 0)
```

## 15.4 Saisie agent

### Écran clôture

| Champ | Validation |
|-------|------------|
| `counted_cash` | required, ≥ 0 |
| `notes` | optional, required si |variance| > 1000 |
| `closing_gps` | recommended |
| Photo caisse (V3.2) | optional audit |

### Détail coupures (V3.1)

| Coupure XAF | Qté |
|-------------|-----|
| 10000 | 5 |
| 5000 | 10 |
| … | … |

`SUM(denomination * quantity)` doit égaler `counted_cash` ± tolérance 1 XAF.

## 15.5 Seuils configurables

| Paramètre | Défaut | Effet |
|-----------|--------|-------|
| `variance_auto_approve_max` | 500 XAF | closed → approved auto |
| `variance_supervisor_required` | > 500 XAF | pending_close |
| `variance_investigation_threshold` | 10000 XAF | Alerte finance + blocage agent |

## 15.6 Validation superviseur

### API `POST /cash-sessions/{id}/approve`

```json
{
  "decision": "approve",
  "supervisor_notes": "Écart monnaie — validé après vérification"
}
```

Ou `decision: reject` → session repasse `open`, agent re-compte.

### Blocages post-clôture

- Session `approved` : aucun void espèces rétroactif sans procédure finance
- Nouvelle session jour suivant : ouverture indépendante

## 15.7 Offline clôture

1. Clôture enregistrée localement `pending_sync`
2. Encaissements offline doivent être dans le batch **avant** clôture
3. Ordre sync : payments → close session
4. Serveur recalcule `expected_cash` — si écart avec comptage agent, `pending_close` automatique

## 15.8 Rapport clôture PDF (V3.2)

Document `OWE-CS-RPT-{session_number}` :
- Agent, date, opening_float
- Liste paiements espèces
- expected vs counted vs variance
- Signatures agent / superviseur (champs)

## 15.9 Intégration trésorerie (V3.5)

Remise physique espèces à la mairie : enregistrement `cash_deposit` (hors scope V3.0) lié à session `approved`.

## 15.10 KPI clôture

- % sessions avec écart = 0
- Écart moyen absolu par agent
- Délai moyen validation superviseur
- Sessions pending > 48 h (alerte)

## 15.11 Erreurs

| Code | Cas |
|------|-----|
| `SESSION_NOT_OPEN` | Clôture double |
| `PENDING_PAYMENTS` | Paiements cash non synced |
| `DENOMINATION_MISMATCH` | Somme coupures ≠ counted |
