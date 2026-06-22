# Audit de préparation — Roadmap financière MAMI 2026–2030

**Document préparatoire à l'implémentation des Sprints 4.2 → 5.0**

| | |
|---|---|
| **Version** | 1.0 |
| **Date** | juin 2026 |
| **Statut** | Pré-audit — aucune implémentation déclenchée |
| **Socle validé** | Sprint 4.0 · Sprint 4.1 · Sprint 4.1.1 · Tag `v1.0-recouvrement-terrain` |
| **Destinataires** | Maire · SG · DAF · Receveur · Contrôleur financier · DSI · Trésor Public |

---

## Objet du document

Ce document **ne remplace pas** une mission d'audit externe. Il prépare :

1. l'inventaire du socle opérationnel ;
2. la cartographie cible officielle ;
3. l'analyse des dépendances et risques ;
4. les contrôles internes à intégrer dès la conception ;
5. les recommandations par gouvernance ;
6. la confirmation de l'ordre d'exécution des sprints.

**Aucun développement, migration ou changement API n'est requis pour produire ce document.**

---

## A. Situation actuelle

### A.1 Modules opérationnels (validés)

| Domaine | Module | Référence | Statut |
|---------|--------|-----------|--------|
| Recensement | Registre économique, QR commerce | Sprint 3.2 | ✅ Production |
| QR First | Scan → situation fiscale / encaissement | Sprint 3.4 | ✅ Production |
| Fiscalité | Situation fiscale, créances, obligations | Sprint 3.3 | ✅ Production |
| Encaissement | Caisse terrain, collections | Sprint 3.3 / v1.0 | ✅ Production |
| Quittances | PDF + impression Bluetooth | Sprint 3.3.1 | ✅ Production |
| Contrôles | Visites terrain, field visits | Sprint 3.4 | ✅ Production |
| Synchronisation | Statut sync agents | Sprint 3.4 | ✅ Production |
| Gouvernance | Missions, journal, dashboard DAF | Sprint 4.0 | ✅ Production |
| Visa financier | Workflow missions 6 états | Sprint 4.1 | ✅ Production |
| UX Finance | Portail `/finance/home` par rôle | Sprint 4.1.1 | ✅ Production |
| Supervision | Caisses ouvertes, clôture admin | Sprint 4.0 | ✅ Production |
| Reversement | Brouillons `municipal_treasury_remittances` | Sprint 4.0 | ✅ Préparation (cycle complet = 4.2) |

### A.2 Socle technique actuel

| Composant | État |
|-----------|------|
| API Laravel `/api/municipality/*` | Opérationnelle |
| Auth Sanctum + rôles `MamiRole` | Opérationnelle |
| Flutter `mami_client` (APK unique mairie) | Opérationnelle |
| Migrations finance 4.0 / 4.1 | Déployées (VPS) |
| Feature flags | `MAMI_MUNICIPALITY_REQUIRE_MISSION`, `MAMI_FINANCE_LEGACY_MISSION_AUTHORIZE` |
| Infrastructure cible | [ROADMAP_INFRASTRUCTURE_MAMI_2026_2030.md](architecture/ROADMAP_INFRASTRUCTURE_MAMI_2026_2030.md) |

### A.3 Lacunes identifiées (à combler par la roadmap)

| Lacune | Sprint cible |
|--------|--------------|
| Cycle reversement Trésor complet (bordereau → confirmation) | 4.2 |
| Plan comptable, journal, grand livre, balance | 4.3 |
| Budget prévisionnel / exécuté | 4.4 |
| RH, paie, congés | 4.5 |
| Circuit dépense (BC → paiement) | 4.6 |
| Conformité administrative tiers (documenté, non implémenté) | 4.6.x |
| Patrimoine communal | 4.7 |
| Trésorerie consolidée | 4.8 |
| Marchés publics | 4.9 |
| Portail citoyen finances / transparence | 5.0 |

---

## B. Cartographie cible

### B.1 Architecture fonctionnelle cible (ordre officiel)

```
SOCLE TERRAIN (v1.0) ──► GOUVERNANCE 4.0/4.1 ──► 4.2 TRÉSOR
                                                      │
        ┌─────────────────────────────────────────────┼─────────────────────────────┐
        ▼                     ▼                     ▼                             ▼
   4.3 COMPTABILITÉ      4.4 BUDGET           4.5 RH & PAIE                  4.8 TRÉSORERIE
        │                     │                     │                             │
        └──────────┬──────────┘                     │                             │
                   ▼                                 │                             │
            4.6 PRESTATAIRES ◄── 4.6.x Conformité ───┤                             │
                   │                                 │                             │
                   ▼                                 ▼                             ▼
            4.7 PATRIMOINE                    4.9 MARCHÉS PUBLICS ◄──────────────┘
                   │
                   ▼
            5.0 PORTAIL CITOYEN
```

### B.2 Fiche synthétique par sprint

| Sprint | Intitulé | Objectif principal | Document |
|--------|----------|-------------------|----------|
| **4.2** | Reversement Trésor | Cycle bordereau → dépôt → confirmation Trésor Public | [SPRINT_4_2_REVERSEMENT_TRESOR.md](SPRINT_4_2_REVERSEMENT_TRESOR.md) |
| **4.3** | Comptabilité | Plan comptable SYSCOHADA, journal, grand livre, écritures auto | [SPRINT_4_3_COMPTABILITE.md](SPRINT_4_3_COMPTABILITE.md) |
| **4.4** | Budget | Prévisionnel, exécuté, taux d'exécution, dashboard DAF | [SPRINT_4_4_BUDGET.md](SPRINT_4_4_BUDGET.md) |
| **4.5** | RH & Paie | Effectifs, grades, bulletins, congés | [SPRINT_4_5_RH.md](SPRINT_4_5_RH.md) |
| **4.6** | Prestataires | BC → service fait → visa → ordonnancement → paiement | [SPRINT_4_6_PRESTATAIRES.md](SPRINT_4_6_PRESTATAIRES.md) |
| **4.6.x** | Conformité admin. | Pièces CNSS, RCCM, etc. — **spec only** | [SPRINT_4_6_PRESTATAIRES.md](SPRINT_4_6_PRESTATAIRES.md) §13 |
| **4.7** | Patrimoine | Biens communaux, inventaire, amortissements | *À rédiger* |
| **4.8** | Trésorerie | Position banque/caisse, prévisions flux, rapprochements | *À rédiger* |
| **4.9** | Marchés Publics | Procédures, attribution, exécution marchés | *À rédiger* |
| **5.0** | Portail Citoyen | Transparence budgétaire, signalements enrichis | *À rédiger* |

### B.3 Principes d'architecture transverses

- **Un APK** mobile mairie, menus par rôle (pattern 4.1.1)
- **API modulaire** sous `/api/municipality/finance/*`, `/compliance/*`, etc.
- **Journal unique** `municipal_finance_journal_entries` + audit trail
- **Feature flags** pour activation progressive sans régression terrain
- **Compatibilité** recouvrement v1.0 non négociable

---

## C. Dépendances entre modules

### C.1 Matrice des prérequis

| Sprint | Prérequis techniques | Prérequis métier | Prérequis comptables | Prérequis budgétaires |
|--------|-------------------|------------------|---------------------|----------------------|
| **4.2** | 4.1 workflow, journal | Missions approuvées, caisses | — | — |
| **4.3** | 4.2 reversements confirmés | Encaissements, clôtures caisse | — | — |
| **4.4** | 4.3 écritures recettes | Budget voté conseil (données) | Plan comptable | — |
| **4.5** | 4.0 rôles | Organigramme mairie | Comptes charges RH (4.3) | Lignes masse salariale (4.4) |
| **4.6** | 4.3, 4.4 | Répertoire fournisseurs | Écritures 401/512 | Lignes dépenses |
| **4.6.x** | 4.6 core, stockage fichiers | Référentiel pièces | Blocage paiement (4.6) | — |
| **4.7** | 4.3 | Inventaire patrimoine papier | Comptes immobilisations | Dotations amortissement |
| **4.8** | 4.2, 4.3 | Comptes bancaires mairie | Rapprochement bancaire | — |
| **4.9** | 4.6, 4.6.x, 4.4 | Règlementation marchés | — | Enveloppes marchés |
| **5.0** | 4.4 (public), API stable | Validation communication Maire | — | Données exécution publiées |

### C.2 Dépendances critiques (chemin critique)

```
4.1 → 4.2 → 4.3 → 4.4 → 4.6 → 4.9
              ↘ 4.5 (parallèle après 4.0)
              ↘ 4.7 (après 4.3)
              ↘ 4.8 (après 4.2 + 4.3)
4.4 + 4.8 + 4.9 → 5.0
```

### C.3 Couplages avec le socle terrain

| Module cible | Lien terrain | Risque si mal conçu |
|--------------|--------------|---------------------|
| 4.3 Compta | Encaissements → écritures REC | Double comptabilisation |
| 4.6.x Conformité | Opérateurs recensés | Blocage encaissement |
| 4.9 Marchés | Étals / opérateurs | Confusion recensement / marché |
| 5.0 Portail | Signalements 3.x | Fuite données fiscales |

**Mitigation :** feature flags + tests non-régression `v1.0-recouvrement-terrain`.

---

## D. Risques de projet

### D.1 Qualité des données

| Risque | Impact | Mitigation |
|--------|--------|------------|
| Opérateurs sans NIF / RCCM fiable | Conformité 4.6.x impossible | Campagne mise à jour registre 3.2 |
| Historique encaissements incomplet | Compta 4.3 déséquilibrée | Reprise manuelle exercice N |
| Doublons fournisseurs | Paiements erronés | Référentiel unique + dédoublonnage |
| Montants caisse ≠ journal | Audit Trésor rejeté | Réconciliation quotidienne (4.8) |

### D.2 Reprise historique

| Périmètre | Recommandation |
|-----------|----------------|
| Exercice comptable en cours | Ouverture 4.3 à date arbitrée (ex. 01/01/2027) |
| Budget | Import PDF voté → saisie lignes 4.4 |
| RH | Saisie progressive effectifs 4.5 |
| Patrimoine | Inventaire physique avant 4.7 |

### D.3 Sécurité

| Domaine | Exigence |
|---------|----------|
| Authentification | Sanctum, rotation tokens, MFA admin (recommandé) |
| Habilitations | RBAC granulaire par sprint |
| Fichiers (4.2, 4.6.x) | Stockage chiffré, antivirus, accès journalisé |
| API publique 5.0 | Données anonymisées, rate limiting |

### D.4 Auditabilité

- Journal immuable (`municipal_finance_journal_entries`)
- Workflow 4.1 comme modèle pour 4.2, 4.6, 4.9
- Snapshots conformité 4.6.x
- Export CSV/PDF pour commissaire aux comptes

### D.5 Sauvegardes

Référence : [ROADMAP_INFRASTRUCTURE_MAMI_2026_2030.md](architecture/ROADMAP_INFRASTRUCTURE_MAMI_2026_2030.md)

| Exigence | Cible |
|----------|-------|
| Backup DB quotidien | RPO 24 h |
| Backup fichiers (pièces jointes) | Synchronisé avec DB |
| PRA | Restauration < 4 h (Phase 2 infra) |
| Tests restauration | Trimestriel |

### D.6 Montée en charge

| Phase | Charge estimée | Infra |
|-------|----------------|-------|
| 4.2–4.4 | Faible (DAF + agents) | Phase 1 actuelle |
| 4.5–4.6 | Moyenne | Phase 1 |
| 4.9 + 5.0 | Élevée (citoyens) | Phase 2 LB + réplica |

### D.7 Interconnexions externes

| Organisme | Sprint concerné | Mode envisagé | Risque |
|-----------|-----------------|---------------|--------|
| **Trésor Public** | 4.2, 4.8 | Bordereau PDF + confirmation manuelle v1 ; API future | Délais validation |
| **CNSS** | 4.5, 4.6.x | Import attestations PDF v1 ; API v2 | Format non standardisé |
| **CNAMGS** | 4.5, 4.6.x | Idem CNSS | Idem |
| **DGI / fiscal** | 4.3, 4.6.x | Attestations scannées | Fraude documentaire |
| **Banques** | 4.8 | Relevés import CSV | Rapprochement manuel v1 |

---

## E. Contrôles internes à prévoir

### E.1 Ségrégation des tâches (SoD)

| Processus | Créateur | Valideur | Exécuteur |
|-----------|----------|----------|-----------|
| Mission financière | DAF adjoint | Contrôleur → DAF | Agent terrain |
| Reversement Trésor | Receveur | Contrôleur → DAF → Receveur | Banque |
| Bon de commande | Service demandeur | DAF / SG | — |
| Paiement fournisseur | Ordonnateur | DAF | Trésor / caisse |
| Écriture comptable | Système (auto) | DAF (manuel) | — |
| Document conformité | Fournisseur / agent | DAF / contrôleur | — |

### E.2 Validation hiérarchique

- Réutiliser le pattern workflow 4.1 (`financial_mission_approvals`)
- Étendre à : reversements (4.2), BC (4.6), marchés (4.9)
- Motif de rejet obligatoire, immuable

### E.3 Audit trail et journalisation

| Événement | Support |
|-----------|---------|
| Toute transition workflow | `municipal_finance_journal_entries` |
| Modifications référentiels | `FiscalAuditService` |
| Connexions sensibles | Log applicatif + SIEM (Phase 3) |
| Exports données | Traçabilité export_id |

### E.4 Gestion des habilitations

- Matrice rôles : [MAMI_ROLE_PERMISSION_MATRIX.md](MAMI_ROLE_PERMISSION_MATRIX.md) — à enrichir par sprint
- Revue trimestrielle des comptes DAF / Receveur / Contrôleur
- Principe du moindre privilège

### E.5 Contrôle des modifications

- Missions : brouillon seul modifiable (4.1)
- Budget voté : révision formelle (4.4 `budget_revisions`)
- Plan comptable : versionné
- Pièces conformité : historisation versions (4.6.x)

### E.6 Réconciliation comptable

| Réconciliation | Fréquence | Sprint |
|----------------|-----------|--------|
| Caisse terrain vs encaissements | Quotidienne | 4.0 → 4.3 |
| Reversement vs dépôt banque | Par opération | 4.2 |
| Banque vs compta | Mensuelle | 4.8 |
| Budget exécuté vs compta | Mensuelle | 4.4 |

### E.7 Contrôle des recettes encaissées

- Quittance officielle obligatoire (3.3)
- Lien `municipal_payments` → écriture REC (4.3)
- Écarts clôture caisse → compte 658/758

### E.8 Contrôle des reversements

- Montant reversement = somme caisses période (tolérance configurable)
- Double validation DAF + Receveur avant dépôt
- Pièce jointe bordereau + reçu Trésor obligatoires (4.2)

---

## F. Recommandations d'audit par gouvernance

### F.1 Maire

1. Valider l'**ordre officiel** 4.2 → 5.0 et ne pas paralléliser 4.6 avant 4.3/4.4.
2. Exiger un **rapport de recette** signé avant chaque activation feature flag en production.
3. Prévoir une **communication** sur le Portail 5.0 (transparence) après stabilisation 4.4.
4. Arbitrer le **seuil** de délégation SG vs DAF pour les marchés (4.9).

### F.2 Secrétaire Général

1. Piloter la **reprise historique** budget et organigramme RH avant 4.4 / 4.5.
2. Valider le **référentiel documentaire** 4.6.x (pièces obligatoires fournisseurs).
3. Co-signer les **marchés** au-dessus du seuil configuré.
4. Organiser la **commission de recette** par sprint avec procès-verbal.

### F.3 DAF

1. **Priorité immédiate :** Sprint 4.2 (reversement Trésor) — visibilité institutionnelle.
2. Préparer le **plan comptable simplifié** Owendo avant développement 4.3.
3. Maintenir le **mode legacy** désactivé progressivement après recette workflow 4.1.
4. Définir les **seuils** BC et paiement avant 4.6.
5. Piloter la **réconciliation** caisse / compta dès 4.3.

### F.4 Receveur municipal

1. Participer à la **spécification détaillée** 4.2 (bordereau, banque, reçu).
2. Préparer les **modèles papier** actuels pour alignement numérique.
3. Tester en **pilote** le premier reversement tracé avant généralisation.
4. Anticiper la **formation** 4.8 (rapprochement bancaire).

### F.5 Contrôleur financier

1. Utiliser la **file de validation** 4.1 en conditions réelles avant extension 4.2.
2. Définir la **checklist** de contrôle reversement (montant, pièces, délais).
3. Préparer les **grilles d'audit** pour 4.6 (BC, service fait, facture).
4. Valider les règles **4.6.x** conformité avant tout blocage paiement.

### F.6 DSI

1. Maintenir **git pull + migrate** systématique sur VPS après chaque sprint.
2. Planifier **Phase 2 infra** avant 5.0 (charge citoyenne).
3. Sécuriser le **stockage fichiers** (4.2, 4.6.x) — hors webroot public.
4. Automatiser les **backups** et tests de restauration trimestriels.
5. CI : exécuter PHPUnit + tests Flutter sur chaque PR finance.

---

## G. Priorisation — ordre officiel confirmé

L'ordre d'exécution **reste inchangé** et constitue la référence pour tout audit ou planification :

| Ordre | Sprint | Statut juin 2026 |
|-------|--------|------------------|
| — | 4.0 Gouvernance financière | ✅ Validé |
| — | 4.1 Visa financier | ✅ Validé |
| — | 4.1.1 UX portail Finance | ✅ Validé |
| **1** | **4.2** Reversement Trésor | 🔜 Prochain |
| **2** | **4.3** Comptabilité | Planifié |
| **3** | **4.4** Budget | Planifié |
| **4** | **4.5** RH & Paie | Planifié |
| **5** | **4.6** Prestataires | Planifié |
| **5b** | **4.6.x** Conformité administrative | Documenté — non implémenté |
| **6** | **4.7** Patrimoine | À spécifier |
| **7** | **4.8** Trésorerie | À spécifier |
| **8** | **4.9** Marchés Publics | À spécifier |
| **9** | **5.0** Portail Citoyen | À spécifier |

### G.1 Jalons institutionnels indicatifs

| Trimestre | Jalon |
|-----------|-------|
| T3 2026 | 4.2 — Premier reversement Trésor numérique |
| T4 2026 | 4.3 — Journal alimenté par recettes terrain |
| T1 2027 | 4.4 — Budget primitif intégré |
| T2 2027 | 4.5 — RH agents + 4.6 pilote prestataires |
| T3 2027 | 4.7 Patrimoine + 4.8 Trésorerie |
| T4 2027 | 4.9 Marchés + 4.6.x conformité |
| 2028 | 5.0 Portail Citoyen |

### G.2 Critères de passage au sprint suivant

| Critère | Responsable |
|---------|-------------|
| Rapport de recette signé | SG + DAF |
| Tests automatisés verts (CI) | DSI |
| Matrice non-régression terrain OK | Contrôleur |
| Migration production sans incident | DSI |
| Formation utilisateurs clés | DAF / Receveur |

---

## Annexes

| Document | Lien |
|----------|------|
| Feuille de route 4.1–4.6 | [FEUILLE_DE_ROUTE_GOUVERNANCE_FINANCIERE_4_1_4_6.md](FEUILLE_DE_ROUTE_GOUVERNANCE_FINANCIERE_4_1_4_6.md) |
| Infrastructure 2026–2030 | [architecture/ROADMAP_INFRASTRUCTURE_MAMI_2026_2030.md](architecture/ROADMAP_INFRASTRUCTURE_MAMI_2026_2030.md) |
| Audit migrations 4.1 | [AUDIT_MIGRATIONS_SPRINT_4_1.md](AUDIT_MIGRATIONS_SPRINT_4_1.md) |
| Prestataires + 4.6.x | [SPRINT_4_6_PRESTATAIRES.md](SPRINT_4_6_PRESTATAIRES.md) |

---

## Prochaine action recommandée

**Lancer la spécification détaillée Sprint 4.2** et tenir une **réunion de cadrage audit** (Maire, SG, DAF, Receveur, Contrôleur, DSI) sur la base de ce document — sans engagement de développement avant validation du périmètre 4.2.

---

*Document de préparation audit — MAMI.ga · Commune d'Owendo · Gouvernance financière municipale.*
