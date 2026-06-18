# MAMI Municipality V3 — Dossier d'architecture Fiscalité & Recouvrement

**Version** : 1.0 (document d'architecture — **aucun code**)  
**Date** : juin 2026  
**Statut** : conception validable avant implémentation  
**Branche cible** : `feature/mami-taxi-v2-p2` → release `municipality-v3`

---

## Contexte de départ (production)

| Brique | Statut |
|--------|--------|
| Registre économique V2 (`economic_operators`, `OWE-COM-*`) | En production |
| Fondations V2.5 (QR UUID, `field_visits`, structures paiement/quittance) | En production |
| SIG Owendo (territoire, quartiers, ZOP, zones économiques) | En production |
| Socle Super App (`payments`, `transactions`, `audit_logs`) | En production |
| Module Taxi (`rides`, `drivers`) | **Intouchable** — aucune régression autorisée |

---

## Objectif V3

Transformer l'application Android **Agent Municipal** en **terminal mobile de recouvrement fiscal** :

1. Scanner le QR sécurisé d'un commerce (UUID)
2. Afficher la situation fiscale et le montant dû
3. Encaisser (espèces, Mobile Money)
4. Émettre une quittance officielle (`OWE-RCP-YYYY-NNNNNN`)
5. Synchroniser hors ligne si nécessaire
6. Alimenter le tableau de bord du Maire et la carte SIG fiscale

---

## Documents du dossier

| # | Document | Contenu |
|---|----------|---------|
| 1 | [Architecture générale V3](01_ARCHITECTURE_GENERALE.md) | Vision, couches, modules, contraintes |
| 2 | [Modèle de données détaillé](02_MODELE_DONNEES.md) | Tables, relations, extensions V2.5 |
| 3 | [FiscalCollection Module](03_MODULE_FISCAL_COLLECTION.md) | Encaissement, dettes, obligations |
| 4 | [MunicipalReceipts Module](04_MODULE_MUNICIPAL_RECEIPTS.md) | Quittances, PDF, impression |
| 5 | [CashSession Module](05_MODULE_CASH_SESSION.md) | Sessions de caisse agent |
| 6 | [QR Collection Workflow](06_WORKFLOW_QR_COLLECTION.md) | Scan → encaissement |
| 7 | [Agent Collection Workflow](07_WORKFLOW_AGENT_COLLECTION.md) | Parcours agent terrain |
| 8 | [Brigade Workflow](08_WORKFLOW_BRIGADE.md) | Campagnes et visites groupées |
| 9 | [Mayor Fiscal Dashboard](09_MAYOR_FISCAL_DASHBOARD.md) | KPIs exécutifs |
| 10 | [Cartographie SIG fiscale](10_CARTOGRAPHIE_SIG_FISCALE.md) | Couches carte, filtres |
| 11 | [Intégration Mobile Money](11_INTEGRATION_MOBILE_MONEY.md) | Airtel Money, Moov Money |
| 12 | [Intégration paiement espèces](12_INTEGRATION_PAIEMENT_ESPECES.md) | Caisse physique |
| 13 | [Gestion des annulations](13_GESTION_ANNULATIONS.md) | Void, motifs, contrôles |
| 14 | [Gestion des remboursements](14_GESTION_REMBOURSEMENTS.md) | Refunds, contre-passation |
| 15 | [Gestion des clôtures de caisse](15_GESTION_CLOTURES_CAISSE.md) | Clôture journalière |
| 16 | [Audit et traçabilité](16_AUDIT_TRACABILITE.md) | Journal, conformité |
| 17 | [KPI financiers](17_KPI_FINANCIERS.md) | Indicateurs et formules |
| 18 | [Plan de déploiement V3.0 → V3.5](18_PLAN_DEPLOIEMENT.md) | Jalons, risques, rollback |

---

## Documents liés (V1–V2.5)

| Document | Lien |
|----------|------|
| Fondations V2.5 | `../MUNICIPALITY_V2_5_FOUNDATION_REPORT.md` |
| Recouvrement fiscal (V1 spec) | `../FISCAL_RECOVERY_MODULE_SPEC.md` |
| Schéma BDD GIS | `../GIS_DATABASE_DESIGN.md` |
| Architecture SIG | `../GIS_ARCHITECTURE.md` |
| Référentiel territorial | `../MUNICIPAL_TERRITORIAL_REFERENCE.md` |
| Socle Super App | `../MAMI_SUPER_APP_ARCHITECTURE.md` |

---

## Règle d'or

> **Aucune migration, route, job ou table Taxi ne doit être modifiée par V3.**  
> Le module Municipality s'appuie sur le Core (`payments`, `transactions`) via polymorphisme, sans altérer le module Taxi.
