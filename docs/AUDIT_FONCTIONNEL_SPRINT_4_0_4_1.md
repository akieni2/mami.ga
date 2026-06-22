# Audit fonctionnel — Sprints 4.0 et 4.1

**Préparation d'un Sprint 4.2 propre (Reversement Trésor)**

| | |
|---|---|
| **Version** | 1.0 |
| **Date** | juin 2026 |
| **Périmètre** | Backend Laravel · Flutter `mami_client` · RBAC · workflows · journal · caisses · reversements brouillon |
| **Hors périmètre** | Implémentation 4.2 (ce document ne modifie rien) |
| **Références** | [SPRINT_4_0](SPRINT_4_0_GOUVERNANCE_FINANCIERE.md) · [SPRINT_4_1](SPRINT_4_1_VISA_FINANCIER_IMPLEMENTATION.md) · [SPRINT_4_2](SPRINT_4_2_REVERSEMENT_TRESOR.md) |

---

## Synthèse exécutive

| Domaine | Verdict | Commentaire |
|---------|---------|-------------|
| Rôles financiers | 🟡 **Majoritairement cohérent** | Matrice RBAC solide ; écarts Flutter ↔ API sur clôture admin |
| Permissions | 🟡 **À corriger avant 4.2** | Permissions workflow remittance absentes ; DAF adjoint ambigu |
| Workflows missions | 🟢 **Cohérent** | Machine à états 4.1 respectée ; legacy documenté |
| Journal financier | 🟡 **Incomplet pour 4.2/4.3** | Pas de lien encaissements → journal finance |
| Sessions de caisse | 🟢 **Cohérent** | Mission `approved`, journal, clôture admin tracés |
| Reversements préparatoires | 🔴 **Écart majeur vs 4.2** | Statuts 4.0 ≠ cycle 4.2 ; montant non réconcilié |

**Conclusion :** le socle 4.0/4.1 est **exploitable en production** pour missions, supervision et brouillons reversement. Le Sprint **4.2 doit commencer par une migration d'alignement** des statuts reversement et par la **réconciliation montant ↔ encaissements**, en réutilisant les patterns workflow/journal déjà validés en 4.1.

**Score de préparation 4.2 :** 7/10 — blocages identifiés et actionnables (voir §8).

---

## 1. Rôles financiers

### 1.1 Rôles définis (`MamiRole`)

| Rôle | Slug | Module | Aligné spec |
|------|------|--------|-------------|
| DAF | `daf` | municipality | ✅ |
| DAF adjoint | `daf_adjoint` | municipality | ✅ |
| Contrôleur financier | `controleur_financier` | municipality | ✅ |
| Receveur municipal | `receveur_municipal` | municipality | ✅ |
| Caissier central | `caissier_central` | municipality | ✅ |

Rôles **hors finance** préservés : `municipal_agent`, `municipal_supervisor` — pas de redirection portail Finance (4.1.1) ✅

### 1.2 Matrice rôle → responsabilité métier

| Rôle | Missions | Validation | Caisse terrain | Supervision | Reversement | Portail Finance |
|------|----------|------------|----------------|-------------|-------------|-----------------|
| DAF | CRUD + approve | Toute file | bypass mission | supervise + admin close | view + manage | Complet |
| DAF adjoint | CRUD + submit | File (pas approve API) | — | supervise + **admin close API** | view | Sans approve |
| Contrôleur | view | submit → review | — | supervise + admin close | — | Validation + caisses |
| Receveur | view mission | — | ouverture (mission requise) | — | view + manage | Reversements |
| Caissier central | view | — | ouverture (mission requise) | supervise | — | Caisses seules |
| Agent municipal | — | — | ouverture (flag off) | — | — | Hub terrain |

### 1.3 Écarts identifiés

| ID | Sévérité | Écart | Recommandation |
|----|----------|-------|----------------|
| R-01 | **Majeure** | Flutter `FinanceHomeAccess.canAdminCloseCashSessions` = DAF + Contrôleur **uniquement** ; le backend accorde `municipal.finance.cash_session.admin_close` au **DAF adjoint** | Aligner Flutter sur permissions API **ou** retirer la permission au adjoint (choix métier SG) |
| R-02 | Mineure | Contrôleur a `dashboard.view` en API mais pas tuile Dashboard Flutter | Ajouter tuile ou documenter comme volontaire |
| R-03 | Mineure | Receveur a `dashboard.view` mais pas tuile Dashboard Flutter | Idem — receveur orienté reversements |
| R-04 | Info | Cumul `municipal_agent` + rôle finance → portail Finance prioritaire | Documenter pour comptes pilotes |

---

## 2. Permissions

### 2.1 Permissions finance existantes

| Permission | Usage |
|------------|-------|
| `municipal.finance.dashboard.view` | Dashboard DAF |
| `municipal.finance.mission.view/manage` | CRUD missions |
| `municipal.finance.mission.submit` | Soumission workflow |
| `municipal.finance.mission.controller_review` | Revue / rejet (étape soumise) |
| `municipal.finance.mission.daf_review` | Revue / rejet (étapes DAF) |
| `municipal.finance.mission.authorize` | Approbation finale + file complète |
| `municipal.finance.approval.view_queue` | Files validation |
| `municipal.finance.cash_session.supervise` | Liste caisses ouvertes |
| `municipal.finance.cash_session.admin_close` | Clôture administrative |
| `municipal.finance.journal.view` | Journal finance |
| `municipal.finance.remittance.view/manage` | Reversements brouillon |
| `municipal.cash_session.open_without_mission` | Bypass mission (DAF, admin) |

### 2.2 Cohérence RBAC ↔ contrôleurs

| Endpoint | Contrôle | Cohérent |
|----------|----------|----------|
| `POST /workflow/{id}/submit` | submit **ou** manage | ✅ |
| `POST /workflow/{id}/review` | controller_review (submitted) / daf_review (suite) | ✅ |
| `POST /workflow/{id}/approve` | authorize | ✅ |
| `POST /workflow/{id}/reject` | controller_review si submitted, sinon daf_review | ✅ |
| `POST /workflow/{id}/close` | manage | ✅ |
| `POST /missions/{id}/authorize` | authorize + flag legacy | ✅ |
| `POST /fiscal/cash-sessions/{id}/admin-close` | admin_close | ✅ |
| `POST /finance/remittances` | remittance.manage | ✅ |

### 2.3 Permissions manquantes pour 4.2 (à créer au Sprint 4.2)

| Permission envisagée | Rôle |
|--------------------|------|
| `municipal.finance.remittance.control` | Contrôleur |
| `municipal.finance.remittance.daf_validate` | DAF |
| `municipal.finance.remittance.receveur_validate` | Receveur |
| `municipal.finance.remittance.confirm` | Receveur |
| `municipal.finance.remittance.reject` | Contrôleur, DAF, Receveur |

**Écart P-01 (critique pour 4.2) :** aucune permission granulaire reversement aujourd'hui — seul `manage` sur Receveur/DAF. Le workflow 4.2 **doit** introduire une séparation SoD avant mise en production.

### 2.4 Écarts permissions

| ID | Sévérité | Écart | Recommandation |
|----|----------|-------|----------------|
| P-01 | **Critique (4.2)** | Pas de permissions workflow reversement | Créer dans `RolePermissionSeeder` au sprint 4.2 |
| P-02 | Majeure | DAF adjoint : `manage` + `admin_close` mais **pas** `daf_review` ni `authorize` — ne peut pas approuver (correct) mais peut clôturer caisse admin | Valider avec SG |
| P-03 | Mineure | `EnsureFinanceApprovalsAccess` middleware large (view suffit) | Acceptable ; contrôle fin dans contrôleurs |
| P-04 | Mineure | Receveur : `remittance.manage` sans `mission.submit` | Cohérent métier |

---

## 3. Workflows — missions financières (4.1)

### 3.1 Machine à états implémentée

```
draft → submitted → controller_review → daf_review → approved → closed
              ↘ rejected ↙      ↘ rejected ↙
```

| Transition | Implémentée | Testée |
|------------|-------------|--------|
| draft → submitted | ✅ | ✅ |
| submitted → controller_review | ✅ | ✅ |
| controller_review → daf_review | ✅ | ✅ |
| daf_review → approved | ✅ | ✅ |
| * → rejected (étapes autorisées) | ✅ | ✅ |
| approved → closed | ✅ | partiel |
| draft → approved (skip) | ❌ bloqué | ✅ |
| rejected → draft | ❌ absent | — |

### 3.2 Double champ `status` / `workflow_status`

| État workflow | `status` (legacy) | Cohérent |
|---------------|-------------------|----------|
| approved | `authorized` | ✅ |
| closed | `closed` | ✅ |
| rejected | reste `draft` | 🟡 acceptable (correction = nouvelle mission ou évolution future) |
| draft…daf_review | `draft` | ✅ |

`FinancialMissionService::activeForAgent()` filtre sur `workflow_status = approved` ✅

`isApprovedForCollection()` + flag legacy ✅

### 3.3 Ségrégation des tâches (SoD)

| Règle | Implémentée |
|-------|-------------|
| Même user submit + approve | ✅ `assertNotSameActorAsPriorApproval` |
| Contrôleur ne peut pas approve | ✅ permission `authorize` |
| Skip contrôleur | ✅ bloqué |

### 3.4 Routes dupliquées clôture

| Route | Service | Écart |
|-------|---------|-------|
| `POST /missions/{id}/close` | `FinancialMissionService::close` | Pas d'entrée `financial_mission_approvals` |
| `POST /workflow/{id}/close` | `FinancialMissionWorkflowService::close` | Enregistre approval + journal |

**W-01 (mineure) :** deux chemins de clôture. Recommandation 4.2 : déprécier `/missions/{id}/close` ou déléguer au workflow service.

### 3.5 Legacy `POST /missions/{id}/authorize`

| Flag | Comportement |
|------|--------------|
| `MAMI_FINANCE_LEGACY_MISSION_AUTHORIZE=true` (défaut) | draft → approved + authorized |
| `false` | 422 — force workflow 4.1 |

✅ Cohérent pour migration progressive. **Recommandation :** passer à `false` en prod après recette workflow complète.

### 3.6 File `pendingForUser`

| Rôle | Missions visibles |
|------|-------------------|
| DAF (`authorize`) | Toutes pending (submitted, controller_review, daf_review) |
| Contrôleur | `submitted` uniquement |
| DAF (`daf_review`) | `controller_review`, `daf_review` |

🟡 DAF voit toute la file — acceptable pour supervision.

---

## 4. Journal financier (`municipal_finance_journal_entries`)

### 4.1 Catalogue des événements enregistrés

| `event_type` | Source | `financial_mission_id` | `cash_session_id` |
|--------------|--------|------------------------|-------------------|
| `mission.created` | FinancialMissionService | ✅ | — |
| `mission.updated` | FinancialMissionService | ✅ | — |
| `mission.authorized` | Legacy authorize | ✅ | — |
| `mission.approved` | Legacy + workflow | ✅ | — |
| `mission.submitted` | Workflow | ✅ | — |
| `mission.reviewed` | Workflow (+ `review_stage` payload) | ✅ | — |
| `mission.rejected` | Workflow (+ `reason`) | ✅ | — |
| `mission.closed` | Workflow / Service | ✅ | — |
| `cash_session.opened` | CashSessionService | ✅ si mission | ✅ |
| `cash_session.closed` | CashSessionService | ✅ si lié | ✅ |
| `cash_session.admin_closed` | CashSessionService | ✅ si lié | ✅ |
| `remittance.draft_created` | TreasuryRemittanceService | — | — |

### 4.2 Événements absents (impact 4.2 / 4.3)

| Événement attendu | Sprint | Sévérité |
|-------------------|--------|----------|
| `payment.collected` (finance journal) | 4.3 | **Critique compta** |
| `remittance.controlled` … `remittance.confirmed` | 4.2 | **Critique trésor** |
| `mission.resubmitted` | futur | Mineure |

**J-01 :** les encaissements (`MunicipalPayment`) sont tracés via `FiscalAuditService` / `field_visits` mais **pas** dans `municipal_finance_journal_entries`. La comptabilité 4.3 devra brancher un listener — prévoir dès 4.2 la **réconciliation reversement ↔ paiements** pour alimenter le journal.

### 4.3 Qualité des entrées

| Critère | Statut |
|---------|--------|
| `actor_id` renseigné | ✅ |
| `occurred_at` | ✅ |
| `payload` JSON contextuel | ✅ (workflow_status, review_stage, amounts) |
| Immutabilité (pas de DELETE API) | ✅ |
| Lien mission sur clôture caisse | ✅ via relation session |

### 4.4 Historique parallèle `financial_mission_approvals`

✅ Cohérent avec workflow 4.1 — complète le journal pour les missions.

⚠️ Clôture via `FinancialMissionService::close` **n'alimente pas** `financial_mission_approvals` (W-01).

---

## 5. Sessions de caisse

### 5.1 Règles d'ouverture

| Condition | Comportement |
|-----------|--------------|
| `MAMI_MUNICIPALITY_REQUIRE_MISSION=false` (défaut) | Agent municipal ouvre sans mission |
| Flag `true` | Mission `approved` requise |
| Rôle `caissier_central` / `receveur_municipal` | Mission **toujours** requise |
| Permission `open_without_mission` | Bypass (DAF, admin) |

✅ Cohérent avec spec 4.0. Tests : `CashSessionMissionTest`.

### 5.2 Lien mission ↔ caisse

- `cash_sessions.financial_mission_id` renseigné à l'ouverture si mission active ✅
- Journal `cash_session.opened` référence mission ✅
- `activeForAgent` : `workflow_status = approved` + dates validité ✅

### 5.3 Clôture

| Type | `closure_type` | Journal | Permission |
|------|----------------|---------|------------|
| Agent | `agent` | `cash_session.closed` | agent propriétaire |
| Administrative | `administrative` | `cash_session.admin_closed` | `admin_close` |

✅ `calculateExpectedAmount` = opening + encaissements espèces completed ✅

### 5.4 Écarts caisse

| ID | Sévérité | Écart | Recommandation |
|----|----------|-------|----------------|
| C-01 | Mineure | Pas de journal finance sur **encaissement** individuel | Prévoir 4.3 |
| C-02 | Info | Dashboard `pending_validation_amount_xaf` = somme `expected_amount` caisses liées à missions **en validation** — heuristique correcte | Documenter |
| C-03 | Mineure | Session ouverte sans mission (agent standard) : `financial_mission_id` null | OK pour v1.0 ; 4.2 reversement devra agréger par période/agent |

---

## 6. Reversements préparatoires (4.0)

### 6.1 État actuel

| Élément | Implémentation |
|---------|----------------|
| Table | `municipal_treasury_remittances` |
| Statuts enum | `draft` · `pending` · `remitted` · `cancelled` |
| API | `GET/POST /finance/remittances` |
| Service | `createDraft`, `listRecent` |
| Journal | `remittance.draft_created` uniquement |
| Lien paiements | **Absent** |
| Lien caisses | **Absent** |
| Pièces jointes | **Absent** |
| Workflow validation | **Absent** |

### 6.2 Écart avec spec Sprint 4.2

| 4.0 (actuel) | 4.2 (cible) |
|--------------|-------------|
| `pending` | `controlled` |
| `remitted` | `deposited` / `confirmed` |
| — | `daf_validated`, `receveur_validated` |
| Montant saisi manuellement | Montant = agrégation paiements / caisses |
| `validated_by` unique | `controlled_by`, `daf_validated_by`, `receveur_validated_by` |

**RT-01 (critique) :** migration de données et remplacement enum **obligatoire** en tête de 4.2.

**RT-02 (critique) :** aucun contrôle montant reversement vs encaissements — risque d'erreur avant compta 4.3.

**RT-03 (majeure) :** pas de table pivot `remittance_payments` — spec 4.2 §3.

### 6.3 Rôles reversement actuels

| Rôle | view | manage | Suffisant 4.0 | Suffisant 4.2 |
|------|------|--------|---------------|---------------|
| DAF | ✅ | ✅ | ✅ brouillon | ❌ workflow |
| Receveur | ✅ | ✅ | ✅ | ❌ workflow |
| DAF adjoint | ✅ | ❌ | 🟡 lecture seule | À définir (préparation brouillon ?) |
| Contrôleur | ❌ | ❌ | — | ❌ doit contrôler |

### 6.4 Dashboard DAF

Compteurs reversement utilisent `TreasuryRemittanceStatus::Draft`, `Pending`, `Remitted` — **à réaligner** sur nouveaux statuts 4.2 (`DafDashboardService`).

---

## 7. Cohérence transversale Flutter ↔ API

| Fonctionnalité | API | Flutter 4.1.1 | Aligné |
|----------------|-----|---------------|--------|
| Redirection login finance | rôles | `postAuthRoute` | ✅ |
| Portail Finance | permissions implicites | `FinanceHomeAccess` par rôle | 🟡 R-01 |
| Workflow missions | `/workflow/*` | écrans detail + queue | ✅ |
| Legacy authorize | `/missions/{id}/authorize` | `authorizeMission()` conservé | ✅ |
| Supervision caisse | admin_close | masqué adjoint | 🟡 R-01 |

---

## 8. Plan d'actions avant Sprint 4.2

### 8.1 Bloquants (à traiter en ouverture 4.2)

| # | Action | Effort |
|---|--------|--------|
| 1 | Migration statuts `TreasuryRemittanceStatus` → cycle 4.2 + mapping `pending`→`controlled`, etc. | 1 j |
| 2 | Créer permissions workflow reversement (P-01) | 0.5 j |
| 3 | Table pivot `remittance_payments` + calcul montant | 2 j |
| 4 | `TreasuryRemittanceWorkflowService` (calque 4.1) | 2–3 j |
| 5 | Événements journal `remittance.*` (6+ types) | 0.5 j |

### 8.2 Recommandés (court terme, peut être 4.2.1)

| # | Action | Effort |
|---|--------|--------|
| 6 | Aligner Flutter `canAdminCloseCashSessions` avec RBAC (R-01) | 0.5 j |
| 7 | Unifier clôture mission sur workflow service (W-01) | 0.5 j |
| 8 | Désactiver legacy authorize en prod (`MAMI_FINANCE_LEGACY_MISSION_AUTHORIZE=false`) après recette | config |
| 9 | Ajouter `rejected → draft` ou procédure métier « nouvelle mission » | 1 j |
| 10 | Tests `TreasuryRemittanceWorkflowTest` (calque mission) | 1 j |

### 8.3 Pour 4.3 (anticiper dès 4.2)

| # | Action |
|---|--------|
| 11 | Listener `payment.collected` → journal finance (J-01) |
| 12 | Idempotence clé `(source_type, source_id)` écritures |

---

## 9. Matrice de non-régression (vérifiée)

| Parcours v1.0 | Impact audit 4.0/4.1 |
|---------------|----------------------|
| Scan QR → situation fiscale | ✅ Non affecté |
| Encaissement terrain | ✅ Non affecté (mission optionnelle) |
| Quittance Bluetooth | ✅ Non affecté |
| Contrôles terrain | ✅ Non affecté |
| Sync status | ✅ Non affecté |
| Agent → hub terrain | ✅ Non affecté (sans rôle finance) |

---

## 10. Tests automatisés existants

| Fichier | Couverture |
|---------|------------|
| `FinancialMissionTest` | Legacy create + authorize |
| `FinancialMissionWorkflowTest` | Chaîne complète, rejet, skip, caisse |
| `FinancialMissionApprovalTest` | Files, historique |
| `FinancialMissionAuthorizationTest` | Legacy on/off |
| `DafDashboardTest` | Structure dashboard |
| `CashSessionMissionTest` | Mission requise, admin close |
| `finance_*_test.dart` | Flutter rôles / menus |

**Lacune :** aucun test reversement au-delà du brouillon. **À ajouter en 4.2.**

---

## 11. Verdict par domaine pour démarrage 4.2

```
┌────────────────────┬──────────┬─────────────────────────────────────┐
│ Domaine            │ Verdict  │ Prêt pour extension 4.2           │
├────────────────────┼──────────┼─────────────────────────────────────┤
│ Rôles              │ 🟢       │ Oui — enrichir permissions        │
│ Permissions        │ 🟡       │ Oui après P-01                    │
│ Workflow missions  │ 🟢       │ Modèle à répliquer pour reversement│
│ Journal            │ 🟡       │ Étendre event types               │
│ Caisses            │ 🟢       │ Source données reversement        │
│ Reversements       │ 🔴       │ Refonte statuts + liens paiements │
└────────────────────┴──────────┴─────────────────────────────────────┘
```

---

## 12. Signatures audit (à compléter)

| Rôle | Nom | Date | Visa |
|------|-----|------|------|
| DAF | | | ☐ Socle 4.0/4.1 validé pour extension 4.2 |
| Contrôleur financier | | | ☐ Workflows missions conformes |
| Receveur | | | ☐ Préparation reversement comprise |
| DSI | | | ☐ Écarts techniques acceptés |
| SG | | | ☐ Go Sprint 4.2 |

---

*Audit fonctionnel MAMI.ga — Owendo · Préparation Sprint 4.2 Reversement Trésor Public.*
