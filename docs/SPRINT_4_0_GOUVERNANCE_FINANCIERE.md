# Sprint 4.0 — Gouvernance Financière

Couche hiérarchique financière au-dessus du recouvrement terrain validé (`v1.0-recouvrement-terrain`).

## Compatibilité v1.0

| Comportement | Par défaut |
|--------------|------------|
| Ouverture caisse agent municipal sans mission | ✅ Autorisée (`MAMI_MUNICIPALITY_REQUIRE_MISSION=false`) |
| Parcours QR → encaissement → quittance | ✅ Inchangé |
| APIs recouvrement existantes | ✅ Inchangées |

Activer la gouvernance stricte :

```env
MAMI_MUNICIPALITY_REQUIRE_MISSION=true
```

Les rôles `caissier_central` et `receveur_municipal` exigent toujours une mission active.

## Lot 4.0.1 — Rôles, permissions, tableau de bord DAF

### Rôles (`MamiRole`)

| Slug | Libellé |
|------|---------|
| `daf` | Directeur des Affaires Financières |
| `daf_adjoint` | DAF adjoint |
| `caissier_central` | Caissier central |
| `controleur_financier` | Contrôleur financier |
| `receveur_municipal` | Receveur municipal |

### Permissions

| Slug | Usage |
|------|-------|
| `municipal.finance.dashboard.view` | Tableau de bord DAF |
| `municipal.finance.mission.view` | Consulter missions |
| `municipal.finance.mission.manage` | CRUD missions brouillon |
| `municipal.finance.mission.authorize` | Autoriser une mission |
| `municipal.finance.cash_session.supervise` | Supervision caisses |
| `municipal.finance.cash_session.admin_close` | Clôture administrative |
| `municipal.finance.journal.view` | Journal financier |
| `municipal.finance.remittance.view` | Consulter reversements |
| `municipal.finance.remittance.manage` | Créer brouillons reversement |
| `municipal.cash_session.open_without_mission` | Bypass mission (admin / DAF) |

### API

`GET /api/municipality/finance/dashboard`

### Flutter

`/municipality/finance` → `DafDashboardScreen`

## Lot 4.0.2 — Missions terrain financières

### Modèle `financial_missions`

| Champ | Description |
|-------|-------------|
| `reference` | OWE-FM-YYYY-XXXXXX |
| `title` | Intitulé mission |
| `agent_id` | Agent affecté |
| `operational_zone_id` | Zone opérationnelle |
| `valid_from` / `valid_until` | Période de validité |
| `status` | `draft` · `authorized` · `closed` |

### API

```
GET    /api/municipality/finance/missions
POST   /api/municipality/finance/missions
GET    /api/municipality/finance/missions/{id}
PUT    /api/municipality/finance/missions/{id}
POST   /api/municipality/finance/missions/{id}/authorize
POST   /api/municipality/finance/missions/{id}/close
GET    /api/municipality/finance/missions/current
```

### Flutter

`/municipality/finance/missions`

## Lot 4.0.3 — Caisse liée à mission + journal

- `cash_sessions.financial_mission_id` — lien mission ↔ session
- `municipal_finance_journal_entries` — journal événements (mission, caisse)
- Ouverture caisse : vérifie mission si configuré
- Audit `FiscalAuditService` conservé en parallèle

## Lot 4.0.4 — Supervision caisses

```
GET  /api/municipality/fiscal/cash-sessions?status=open
POST /api/municipality/fiscal/cash-sessions/{id}/admin-close
```

- `closure_type` : `agent` | `administrative`
- Flutter : `/municipality/finance/cash-supervision`

## Lot 4.0.5 — Préparation Reversement Trésor Public

### Table `municipal_treasury_remittances`

Références `OWE-RT-YYYY-XXXXXX`, statuts `draft` · `pending` · `remitted` · `cancelled`.

### API

```
GET  /api/municipality/finance/remittances
POST /api/municipality/finance/remittances  (brouillon)
GET  /api/municipality/finance/journal
```

Flutter : `/municipality/finance/remittances`

## Migration

```bash
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
```

Fichier : `2026_06_28_100000_create_municipality_sprint4_financial_governance_tables.php`

## Tests

- `FinancialMissionTest`
- `DafDashboardTest`
- `CashSessionMissionTest`

## Déploiement

1. Backend : migrate + seed permissions
2. Assigner rôles DAF aux comptes concernés
3. Rebuild APK mobile
4. Optionnel : activer `MAMI_MUNICIPALITY_REQUIRE_MISSION=true` après création des premières missions
