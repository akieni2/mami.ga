# Sprint 4.1 — Visa et validation financière

**Chaîne de validation hiérarchique missions financières**

| | |
|---|---|
| **Prérequis** | Sprint 4.0 (`financial_missions`, rôles DAF / contrôleur) |
| **Priorité** | P0 |
| **Compatibilité** | Conserver `authorized` comme alias de `approved` en lecture API v1 |

---

## 1. Objectif fonctionnel

Remplacer l'autorisation directe d'une mission (`POST /authorize`) par un **circuit de visa** traçable :

1. Création en **brouillon** par DAF adjoint / gestionnaire
2. **Soumission** pour validation
3. **Revue contrôleur financier**
4. **Revue DAF**
5. **Approbation** ou **rejet motivé**

Seules les missions **approved** autorisent l'ouverture de caisse (si `MAMI_MUNICIPALITY_REQUIRE_MISSION=true`).

---

## 2. Machine à états

```
                    submit
         draft ──────────────► submitted
                                    │
                          controller_approve
                                    ▼
                            controller_review
                                    │
                             daf_approve
                                    ▼
                              daf_review
                                    │
                    ┌───────────────┴───────────────┐
                    ▼                               ▼
               approved                         rejected
              (ex-authorized)                  (motif requis)
```

| État | Code | Acteur suivant |
|------|------|----------------|
| Brouillon | `draft` | Gestionnaire mission |
| Soumise | `submitted` | Contrôleur financier |
| Revue contrôleur | `controller_review` | DAF |
| Revue DAF | `daf_review` | DAF (approbation finale) |
| Approuvée | `approved` | Agent terrain (caisse) |
| Rejetée | `rejected` | Gestionnaire (correction) |

**Migration données :** `authorized` → `approved` ; `closed` inchangé.

---

## 3. Modèle de données

### 3.1 Évolution `financial_missions`

| Colonne | Type | Description |
|---------|------|-------------|
| `workflow_status` | string(30) | Nouveaux états (remplace progressivement `status`) |
| `submitted_at` | timestamp | Date soumission |
| `submitted_by` | FK users | |
| `rejection_reason` | text nullable | Motif si rejected |
| `rejected_at` / `rejected_by` | | |

### 3.2 Nouvelle table `financial_approval_steps`

| Colonne | Description |
|---------|-------------|
| `id` | |
| `approvable_type` | `financial_mission` (polymorphe futur) |
| `approvable_id` | |
| `step` | `submit` · `controller_review` · `daf_review` · `approve` · `reject` |
| `from_status` / `to_status` | |
| `actor_id` | FK users |
| `comment` | text nullable |
| `occurred_at` | timestamp |

### 3.3 Journal

Réutiliser `municipal_finance_journal_entries` + `FiscalAuditService` pour chaque transition.

---

## 4. API

Préfixe : `/api/municipality/finance`

### Workflow missions

| Méthode | Route | Permission | Action |
|---------|-------|------------|--------|
| POST | `/missions/{id}/submit` | `mission.manage` | draft → submitted |
| POST | `/missions/{id}/controller-approve` | `mission.controller_review` | submitted → controller_review |
| POST | `/missions/{id}/daf-approve` | `mission.daf_review` | controller_review → daf_review |
| POST | `/missions/{id}/approve` | `mission.authorize` | daf_review → approved |
| POST | `/missions/{id}/reject` | controller ou DAF | * → rejected |
| GET | `/missions/{id}/approval-history` | `mission.view` | Liste steps |

### Files de validation (queues)

| Méthode | Route | Rôle |
|---------|-------|------|
| GET | `/approvals/pending` | Missions en attente pour l'utilisateur connecté |
| GET | `/approvals/history` | Historique paginé (filtres date, statut) |

### Compatibilité 4.0

| Route legacy | Comportement |
|--------------|--------------|
| `POST /missions/{id}/authorize` | Délègue à `approve` si flag legacy activé ; sinon 410 + message migration |

---

## 5. Permissions à ajouter

| Slug | Rôle typique |
|------|--------------|
| `municipal.finance.mission.submit` | DAF adjoint |
| `municipal.finance.mission.controller_review` | Contrôleur financier |
| `municipal.finance.mission.daf_review` | DAF |
| `municipal.finance.approval.view_queue` | Contrôleur, DAF |

---

## 6. Flutter

| Écran | Route | Description |
|-------|-------|-------------|
| **Mission Validation Queue** | `/municipality/finance/approvals` | Liste missions pending par rôle |
| **Mission Detail** | `/municipality/finance/missions/{id}` | Fiche + actions visa |
| **Validation History** | Onglet / route nested | Timeline `financial_approval_steps` |

### Actions UI

- Boutons contextuels selon rôle et état
- Modal rejet avec motif obligatoire (min 10 caractères)
- Badge couleur par état workflow

---

## 7. Tests PHP

| Test | Scénario |
|------|----------|
| `MissionWorkflowTest::test_full_approval_chain` | draft → approved |
| `MissionWorkflowTest::test_controller_rejects` | rejected + motif |
| `MissionWorkflowTest::test_cannot_skip_controller` | submitted → daf_approve = 422 |
| `MissionWorkflowTest::test_approved_mission_allows_cash_session` | Avec require_mission=true |
| `MissionWorkflowTest::test_legacy_authorize_deprecated` | Sans flag legacy |

---

## 8. Recette terrain

Checklist : `docs/recette/RAPPORT_RECETTE_SPRINT_4_1.md` (à produire à la clôture).

| # | Vérification |
|---|--------------|
| 1 | Mission soumise visible file contrôleur |
| 2 | Rejet avec motif enregistré |
| 3 | Mission approved → agent ouvre caisse |
| 4 | Historique complet dans journal DAF |
| 5 | Recouvrement QR inchangé sans mission (flag off) |

---

## 9. Déploiement

```bash
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder --force
```

Variable optionnelle :

```env
MAMI_FINANCE_LEGACY_MISSION_AUTHORIZE=false
```

---

## 10. Audit sécurité

- [ ] Séparation des tâches : même utilisateur ne peut pas submit + approve
- [ ] Motif rejet immuable (append-only)
- [ ] Permissions testées par rôle (citoyen, agent, contrôleur, DAF)
- [ ] Journal non supprimable

---

## 11. Estimation

| Couche | Effort indicatif |
|--------|------------------|
| Backend | 3–5 j |
| Flutter | 2–3 j |
| Tests + recette | 2 j |
