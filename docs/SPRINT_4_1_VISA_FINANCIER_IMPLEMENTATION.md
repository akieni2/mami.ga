# Sprint 4.1 — Visa financier (implémentation)

**Statut :** implémenté · **Branche :** `feature/mami-taxi-v2-p2`

---

## Résumé

Circuit de validation hiérarchique pour les missions financières, compatible Sprint 4.0 et recouvrement terrain v1.0.

---

## Backend

### Enum

- `FinancialMissionWorkflowStatus` — `draft` · `submitted` · `controller_review` · `daf_review` · `approved` · `rejected` · `closed`

### Migration

- `2026_06_16_200000_add_workflow_to_financial_missions_sprint41.php`
- Colonnes workflow sur `financial_missions`
- Table `financial_mission_approvals`
- Migration données : `authorized` → `approved`

### Services

| Service | Rôle |
|---------|------|
| `FinancialMissionWorkflowService` | Transitions validées + journal + audit |
| `FinancialMissionService` | CRUD legacy + `authorize()` si flag activé |

### API

| Méthode | Route |
|---------|-------|
| GET | `/api/municipality/finance/approvals/pending` |
| GET | `/api/municipality/finance/approvals/history` |
| POST | `/api/municipality/finance/workflow/{id}/submit` |
| POST | `/api/municipality/finance/workflow/{id}/review` |
| POST | `/api/municipality/finance/workflow/{id}/approve` |
| POST | `/api/municipality/finance/workflow/{id}/reject` |
| POST | `/api/municipality/finance/workflow/{id}/close` |
| GET | `/api/municipality/finance/workflow/{id}/history` |

Middleware : `finance.approvals`

### Feature flags

```env
MAMI_FINANCE_LEGACY_MISSION_AUTHORIZE=true   # défaut — POST /authorize conservé
MAMI_MUNICIPALITY_REQUIRE_MISSION=false      # inchangé Sprint 4.0
```

---

## Flutter

| Écran | Route |
|-------|-------|
| File validation | `/municipality/finance/approvals` |
| Détail mission | `/municipality/finance/missions/{id}` |
| Historique | `/municipality/finance/missions/{id}/history` |

---

## Tests

| Fichier | Couverture |
|---------|------------|
| `FinancialMissionWorkflowTest` | Chaîne complète, rejet, skip interdit, caisse |
| `FinancialMissionApprovalTest` | File pending, historique |
| `FinancialMissionAuthorizationTest` | Legacy on/off |
| `financial_approval_queue_test.dart` | Parsing modèles |
| `financial_mission_detail_test.dart` | Détail + historique |

---

## Déploiement

```bash
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
```

---

## Compatibilité

- `status=authorized` maintenu en API pour clients 4.0
- `workflow_status=approved` = mission active caisse
- Recouvrement QR / encaissement inchangé si flags par défaut
