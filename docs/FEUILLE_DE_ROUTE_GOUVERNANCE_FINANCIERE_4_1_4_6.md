# Feuille de route — Gouvernance financière MAMI (Sprints 4.1 → 4.6)

**Transformation progressive en système intégré de gestion financière municipale**

| | |
|---|---|
| **Version** | 1.0 |
| **Date** | juin 2026 |
| **Prérequis validés** | Sprint 4.0 · Tag `v1.0-recouvrement-terrain` |
| **Branche de référence** | `feature/mami-taxi-v2-p2` |
| **Destinataires** | Maire · SG · DAF · DSI · Trésor Public |

---

## Synthèse exécutive

Le recouvrement terrain (QR, encaissement, quittances, contrôles) et les fondations de gouvernance (missions, DAF dashboard, supervision caisses, brouillons reversement) sont **opérationnels**. Les sprints 4.1 à 4.6 construisent, couche par couche, la **chaîne de validation financière**, le **reversement Trésor**, la **comptabilité**, le **budget**, les **RH** et les **prestataires**, sans régression sur le cœur terrain.

```
Recouvrement terrain (v1.0) ──► Gouvernance 4.0 ──► Visa 4.1 ──► Trésor 4.2
                                                      │
                      ┌───────────────────────────────┼───────────────────────────────┐
                      ▼                               ▼                               ▼
               Comptabilité 4.3              Budget 4.4                    RH 4.5 · Prestataires 4.6
```

---

## Socle déjà opérationnel (ne pas casser)

| Module | Référence | Statut |
|--------|-----------|--------|
| Recensement économique | Sprint 3.2 | ✅ |
| QR Commerce | Sprint 3.4 | ✅ |
| Situation fiscale / créances | Sprint 3.3 | ✅ |
| Encaissement terrain | `v1.0-recouvrement-terrain` | ✅ |
| Quittance Bluetooth | Sprint 3.3.1 | ✅ |
| Contrôles terrain | Sprint 3.4 | ✅ |
| Synchronisation | Sprint 3.4 | ✅ |
| Missions financières | Sprint 4.0 | ✅ `draft` · `authorized` · `closed` |
| DAF Dashboard | Sprint 4.0 | ✅ |
| Supervision caisses | Sprint 4.0 | ✅ |
| Brouillons reversement Trésor | Sprint 4.0 | ✅ préparation |

**Règle d'or :** tout sprint 4.x ajoute des tables, routes et écrans **sans modifier le comportement par défaut** des parcours terrain tant qu'une feature flag ou un rôle explicite ne l'exige pas.

---

## Vue d'ensemble des sprints

| Sprint | Intitulé | Priorité | Dépend de | Document |
|--------|----------|----------|-----------|----------|
| **4.1** | Visa et validation financière | P0 | 4.0 | [SPRINT_4_1_VISA_FINANCIER.md](SPRINT_4_1_VISA_FINANCIER.md) |
| **4.2** | Reversement Trésor Public | P0 | 4.1 | [SPRINT_4_2_REVERSEMENT_TRESOR.md](SPRINT_4_2_REVERSEMENT_TRESOR.md) |
| **4.3** | Comptabilité municipale | P1 | 4.2 | [SPRINT_4_3_COMPTABILITE.md](SPRINT_4_3_COMPTABILITE.md) |
| **4.4** | Budget municipal | P1 | 4.3 | [SPRINT_4_4_BUDGET.md](SPRINT_4_4_BUDGET.md) |
| **4.5** | Ressources humaines | P2 | 4.0 | [SPRINT_4_5_RH.md](SPRINT_4_5_RH.md) |
| **4.6** | Gestion des prestataires | P2 | 4.3 · 4.4 | [SPRINT_4_6_PRESTATAIRES.md](SPRINT_4_6_PRESTATAIRES.md) |

**Infrastructure transverse :** [architecture/ROADMAP_INFRASTRUCTURE_MAMI_2026_2030.md](architecture/ROADMAP_INFRASTRUCTURE_MAMI_2026_2030.md) (déjà publié).

---

## Sprint 4.1 — Visa et validation financière

**Objectif :** chaîne hiérarchique Contrôleur → DAF pour les missions (puis extensible aux reversements).

**États cibles :** `draft` → `submitted` → `controller_review` → `daf_review` → `approved` | `rejected`

**Livrables clés :** workflow API, file de validation, historique, écrans Flutter Mission Validation Queue / Detail / History.

→ Détail : [SPRINT_4_1_VISA_FINANCIER.md](SPRINT_4_1_VISA_FINANCIER.md)

---

## Sprint 4.2 — Reversement Trésor Public

**Objectif :** cycle complet bordereau → dépôt → confirmation Trésor.

**États :** `draft` → `controlled` → `daf_validated` → `receveur_validated` → `deposited` → `confirmed`

**Livrables clés :** évolution `municipal_treasury_remittances`, pièces jointes PDF, écrans validation Receveur.

→ Détail : [SPRINT_4_2_REVERSEMENT_TRESOR.md](SPRINT_4_2_REVERSEMENT_TRESOR.md)

---

## Sprint 4.3 — Comptabilité municipale

**Objectif :** journal, grand livre, balance, écritures auto depuis encaissements et reversements.

**Tables :** `financial_accounts`, `journal_entries`, `general_ledger` (+ plan comptable SYSCOHADA simplifié).

→ Détail : [SPRINT_4_3_COMPTABILITE.md](SPRINT_4_3_COMPTABILITE.md)

---

## Sprint 4.4 — Budget municipal

**Objectif :** prévisionnel, exécuté, taux d'exécution, dashboard DAF enrichi.

**Tables :** `budget_years`, `budget_sections`, `budget_lines`, `budget_revisions`

→ Détail : [SPRINT_4_4_BUDGET.md](SPRINT_4_4_BUDGET.md)

---

## Sprint 4.5 — Ressources humaines

**Objectif :** effectifs agents, grades, paie, congés (interface avec missions terrain).

**Tables :** `employees`, `grades`, `payrolls`, `leave_requests`

→ Détail : [SPRINT_4_5_RH.md](SPRINT_4_5_RH.md)

---

## Sprint 4.6 — Gestion des prestataires

**Objectif :** BC → service fait → visa DAF → ordonnancement → paiement.

**Tables :** `suppliers`, `purchase_orders`, `service_certificates`, `supplier_invoices`, `supplier_payments`

→ Détail : [SPRINT_4_6_PRESTATAIRES.md](SPRINT_4_6_PRESTATAIRES.md)

---

## Livrables standard (chaque sprint)

| Livrable | Emplacement type |
|----------|------------------|
| Spécification technique + fonctionnelle | `docs/SPRINT_4_X_*.md` |
| Migration SQL | `database/migrations/2026_*_sprint4_X_*.php` |
| Tests PHP | `tests/Feature/Municipality/Finance/*` |
| Tests Flutter | `mobile/mami_client/test/features/municipality/finance/` |
| Rapport de recette | `docs/recette/RAPPORT_RECETTE_SPRINT_4_X.md` |
| Guide déploiement | Section dans doc sprint + `CHECKLIST_DEPLOIEMENT_VPS_SPRINT3.md` (mise à jour) |
| Audit sécurité | Checklist dans doc sprint · revue rôles `RolePermissionSeeder` |
| Compatibilité | Matrice non-régression ci-dessous |

---

## Matrice de non-régression (obligatoire avant merge)

| Parcours | Test automatisé / recette |
|----------|---------------------------|
| Scan QR → situation fiscale | `OperatorFiscalSummaryTest` |
| Encaissement par créances | Tests collections Sprint 3.3 |
| Ouverture / fermeture caisse | `CashSessionTest` |
| Impression quittance | Recette manuelle Bluetooth |
| Mission active + caisse (si flag) | `CashSessionMissionTest` |
| DAF dashboard | `DafDashboardTest` |
| Sync status | `MunicipalSyncStatusTest` |

---

## Calendrier indicatif

| Trimestre | Sprint | Jalon institutionnel |
|-----------|--------|----------------------|
| T3 2026 | 4.1 | Visa missions validé en comité DAF |
| T4 2026 | 4.2 | Premier reversement Trésor traceable |
| T1 2027 | 4.3 | Journal comptable alimenté par recettes terrain |
| T2 2027 | 4.4 | Budget voté intégré dans MAMI |
| T3 2027 | 4.5 | RH agents municipaux |
| T4 2027 | 4.6 | Circuit prestataires pilote |

---

## Infrastructure (rappel)

Document complet : [architecture/ROADMAP_INFRASTRUCTURE_MAMI_2026_2030.md](architecture/ROADMAP_INFRASTRUCTURE_MAMI_2026_2030.md)

| Phase | Cible | Impact sprints 4.x |
|-------|-------|-------------------|
| Phase 1 (2026) | API 8/32 · DB 8/32 · BKP 4/8 | 4.1 · 4.2 |
| Phase 2 (2027–28) | LB · WEB01/02 · DB01 replica | 4.3 · 4.4 · charge compta |
| Phase 3 (2028–30) | HA · Redis cluster · monitoring | 4.5 · 4.6 · multi-communes |

---

## Prochaine action recommandée

**Démarrer Sprint 4.1** — remplacer l'autorisation directe `POST /missions/{id}/authorize` par le workflow à six états, en conservant un mode compatibilité (`MAMI_FINANCE_LEGACY_MISSION_AUTHORIZE=true`) le temps de la recette.

---

*Document de pilotage MAMI.ga — Gouvernance financière municipale Owendo.*
