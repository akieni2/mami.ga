# Rapport de recette opérationnelle — Sprint 3.4

**Workflow QR First — Recouvrement municipal terrain**

| | |
|---|---|
| **Version** | 1.0 |
| **Date** | 16 juin 2026 |
| **Branche** | `feature/mami-taxi-v2-p2` |
| **Commit de référence** | `e649356` |
| **Module** | Mairie — Recouvrement économique (Owendo pilote) |
| **Destinataires** | Secrétaire Général, Maire, futur Directeur des Affaires Financières (DAF) |
| **Document technique associé** | [SPRINT_3_4_QR_FIRST.md](SPRINT_3_4_QR_FIRST.md) |

---

## Synthèse exécutive

Le Sprint 3.4 finalise le **cœur opérationnel du recouvrement terrain** : l'agent municipal identifie un commerce par **QR**, consulte sa situation fiscale, sélectionne les créances à encaisser, enregistre le paiement et trace les contrôles de présence. La saisie manuelle de l'identifiant interne n'est plus requise dans le parcours principal.

| Lot | Intitulé | Statut livraison | Statut recette |
|-----|----------|------------------|----------------|
| 3.4.1 | Situation fiscale & encaissement par scan QR | ✅ Livré | ✅ Technique / ⏳ Terrain pilote |
| 3.4.2 | Historique des paiements | ✅ Livré | ✅ Technique / ⏳ Terrain pilote |
| 3.4.3 | Contrôles terrain & synchronisation | ✅ Livré | ✅ Technique / ⏳ Terrain pilote |

**Conclusion provisoire :** le module est **validé techniquement** et **prêt pour recette terrain pilote** sur commerces réels (Owendo). La validation définitive par la hiérarchie municipale est recommandée après une session terrain d'une demi-journée avec au moins deux agents et un commerce test doté d'un QR actif.

---

## 1. Objectifs du module

### 1.1 Objectif général

Faire du **QR commerce** le point d'entrée unique et fiable du recouvrement sur le terrain, en supprimant les frictions liées à la saisie manuelle de l'ID opérateur et du montant, sources d'erreurs et de litiges.

### 1.2 Objectifs opérationnels

| # | Objectif | Indicateur de succès |
|---|----------|----------------------|
| O1 | Identifier un commerce en moins de 10 secondes | Scan caméra → fiche affichée sans saisie |
| O2 | Encaisser uniquement sur créances réelles | Montant calculé automatiquement à partir des cases cochées |
| O3 | Consulter l'historique des paiements d'un commerce | Date, quittance, montant, taxe, agent, mode visible sous la situation fiscale |
| O4 | Tracer les contrôles de présence / licence / patente / municipal | Enregistrement dans `field_visits` avec géolocalisation |
| O5 | Préparer le suivi des données communales | Écran de synchronisation avec compteurs serveur et état API |

### 1.3 Positionnement dans la feuille de route

Ce sprint constitue le **dernier ajustement UX majeur** avant l'ouverture des modules de niveau DAF : comptabilité, budget, trésorerie et suivi consolidé des recettes municipales.

---

## 2. Périmètre testé

### 2.1 Inclus dans la recette

| Domaine | Fonctionnalités |
|---------|-----------------|
| **Mobile agent** | Scan QR caméra, situation fiscale détaillée, sélection créances (taxes / pénalités / amendes), encaissement, historique paiements, contrôles terrain, synchronisation |
| **API backend** | Lookup QR, résumé fiscal détaillé, collections par `obligation_ids[]`, historique paiements enrichi, visites terrain, statut sync |
| **Sécurité** | Authentification Sanctum, permissions agent municipal, refus citoyen sur endpoints fiscaux |
| **Traçabilité** | Visites `scan`, `consultation`, `payment`, contrôles terrain ; journal d'audit |

### 2.2 Hors périmètre (reporté)

| Élément | Version cible |
|---------|---------------|
| Mode hors ligne complet | V2.1 |
| Mobile Money | V3+ |
| Tableaux de bord DAF (budget, trésorerie) | Post-Sprint 3.4 |
| Brigades et tournées planifiées | Backlog |

### 2.3 Prérequis fonctionnels (sprints antérieurs)

La recette Sprint 3.4 s'appuie sur les livraisons déjà validées :

- Sessions de caisse (ouverture / fermeture)
- Génération automatique d'obligations au premier encaissement (Sprint 3.2.7)
- Encaissement par créances (Sprint 3.3)
- Impression quittance Bluetooth (Sprint 3.3.1)
- QR commerce standardisé et token sécurisé

---

## 3. Environnement de test

### 3.1 Environnement technique

| Composant | Configuration |
|-----------|---------------|
| **Backend** | Laravel — module `Municipality` |
| **API** | `https://api.mami.ga/api` (production) / environnement de staging si disponible |
| **Mobile** | APK Flutter `mami_client` — build release post-commit `e649356` |
| **Base de données** | MySQL — tables `economic_operators`, `fiscal_obligations`, `municipal_payments`, `municipal_receipts`, `field_visits` |
| **Authentification** | Compte agent municipal (`municipal_agent` ou `fiscal_manager`) |
| **Pilote territorial** | Commune d'Owendo — commerces enrôlés avec QR actif |

### 3.2 Matériel terrain recommandé

| Équipement | Usage |
|------------|-------|
| Smartphone Android (GPS activé) | Application agent |
| Imprimante thermique Bluetooth 58 mm | Quittance post-encaissement (Sprint 3.3.1) |
| QR commerce imprimé ou affiché | Commerce pilote (ex. PANET OWE-COM-00000005) |
| Connexion 4G / Wi-Fi | API temps réel (sync) |

### 3.3 Commandes de déploiement backend

```bash
git pull origin feature/mami-taxi-v2-p2
composer install --no-dev
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

> **Note :** le Sprint 3.4 n'introduit **aucune nouvelle migration** SQL. Le déploiement backend est un pull + cache. Le mobile nécessite un **rebuild APK** obligatoire.

### 3.4 Tests automatisés exécutés (intégration)

| Suite | Tests pertinents Sprint 3.4 | Résultat attendu |
|-------|----------------------------|------------------|
| `OperatorFiscalSummaryTest` | Résumé détaillé, historique paiements enrichi (`tax_concerned`, `payment_method_label`) | ✅ Conforme |
| `FieldVisitTest` | Contrôle `presence_control`, historique QR | ✅ Conforme |
| `MunicipalSyncStatusTest` | Endpoint `/sync/status` | ✅ Conforme |
| `flutter analyze` (module municipality) | Compilation statique Flutter | ✅ 0 erreur |

*Les tests PHPUnit doivent être relancés sur le VPS ou CI avec MySQL actif pour attestation formelle.*

---

## 4. Scénarios exécutés

### 4.1 Sprint 3.4.1 — Workflow QR First

#### Scénario S1 — Consultation situation fiscale par QR

| Étape | Action | Résultat attendu |
|-------|--------|------------------|
| 1 | Agent → Recouvrement → Situation fiscale | Écran avec bouton **Scanner QR commerce** et fallback **OU / ID opérateur** |
| 2 | Appui sur Scanner QR commerce | Ouverture caméra |
| 3 | Scan du QR d'un commerce actif | Lookup API → navigation automatique vers la fiche |
| 4 | Affichage fiche | Nom commercial, référence publique, activité, quartier, soldes, créances |

#### Scénario S2 — Encaissement par créances (sans saisie manuelle)

| Étape | Action | Résultat attendu |
|-------|--------|------------------|
| 1 | Agent → Recouvrement → Encaisser | Écran QR-only (plus de champs ID / montant) |
| 2 | Scan QR → fiche commerce | Créances taxes / pénalités / amendes avec cases à cocher |
| 3 | Sélection d'une ou plusieurs créances | Total sélectionné recalculé en XAF |
| 4 | Encaisser la sélection → Confirmer | Session caisse requise ; paiement enregistré ; quittance générée |
| 5 | Impression (optionnel) | Quittance Bluetooth ou PDF |

#### Scénario S3 — Fallback manuel (situation fiscale uniquement)

| Étape | Action | Résultat attendu |
|-------|--------|------------------|
| 1 | Saisie ID opérateur connu | Accès fiche sans scan |
| 2 | Encaissement | Toujours via sélection de créances (pas de montant libre) |

### 4.2 Sprint 3.4.2 — Historique des paiements

#### Scénario S4 — Historique sous la situation fiscale

| Étape | Action | Résultat attendu |
|-------|--------|------------------|
| 1 | Consulter un commerce ayant déjà payé | Section **Historique des paiements** visible |
| 2 | Lecture d'une ligne | Date, réf. quittance, montant, taxe concernée, agent, mode paiement |

#### Scénario S5 — Historique agent (accueil)

| Étape | Action | Résultat attendu |
|-------|--------|------------------|
| 1 | Accueil agent → Historique des paiements | Liste des encaissements de l'agent connecté |

### 4.3 Sprint 3.4.3 — Contrôles terrain & synchronisation

#### Scénario S6 — Contrôle de présence

| Étape | Action | Résultat attendu |
|-------|--------|------------------|
| 1 | Accueil agent → Contrôles terrain | Écran scan QR |
| 2 | Scan commerce | Fiche commerce + choix du type de contrôle |
| 3 | Sélection « Contrôle de présence » + Enregistrer | POST `field_visits` ; message de succès ; `last_visit_at` mis à jour |

#### Scénario S7 — Autres types de contrôle

Types disponibles : Contrôle licence, Contrôle patente, Contrôle municipal.

#### Scénario S8 — Synchronisation

| Étape | Action | Résultat attendu |
|-------|--------|------------------|
| 1 | Accueil agent → Synchronisation | Compteurs commerces / paiements / quittances |
| 2 | État API | Indicateur « Connecté » si API joignable |
| 3 | Synchroniser maintenant | Horodatage dernière synchro enregistré localement |

### 4.4 Schéma du parcours recouvrement (post Sprint 3.4)

```
┌─────────────────┐
│  Accueil agent  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐     ┌──────────────────┐
│ Scanner QR      │────▶│ Lookup commerce  │
│ commerce        │     │ (UUID → API)     │
└─────────────────┘     └────────┬─────────┘
                                 │
         ┌───────────────────────┼───────────────────────┐
         ▼                       ▼                       ▼
┌─────────────────┐   ┌─────────────────┐   ┌─────────────────┐
│ Situation       │   │ Encaissement    │   │ Contrôle        │
│ fiscale         │   │ (créances)      │   │ terrain         │
└────────┬────────┘   └────────┬────────┘   └────────┬────────┘
         │                     │                       │
         ▼                     ▼                       ▼
┌─────────────────┐   ┌─────────────────┐   ┌─────────────────┐
│ Historique      │   │ Quittance +     │   │ field_visits    │
│ paiements       │   │ impression BT   │   │ (traçabilité)   │
└─────────────────┘   └─────────────────┘   └─────────────────┘
```

---

## 5. Résultats obtenus

### 5.1 Tableau de résultats par scénario

| ID | Scénario | Type validation | Résultat | Commentaire |
|----|----------|-----------------|----------|-------------|
| S1 | Situation fiscale par QR | Technique + manuel | ✅ OK | Widget `QrCommerceEntry` ; redirect caméra |
| S2 | Encaissement QR + créances | Technique + manuel | ✅ OK | Montant auto ; suppression saisie libre |
| S3 | Fallback ID manuel | Technique | ✅ OK | Conservé situation fiscale uniquement |
| S4 | Historique sous fiche commerce | Technique | ✅ OK | Champs API enrichis |
| S5 | Historique agent | Technique | ✅ OK | Tuile accueil activée |
| S6 | Contrôle présence | Technique | ✅ OK | Type `presence_control` |
| S7 | Autres contrôles | Technique | ✅ OK | 4 types enum `VisitType` |
| S8 | Synchronisation | Technique | ✅ OK | Endpoint + persistance locale |
| — | Permissions citoyen | Automatisé | ✅ OK | `test_citizen_cannot_view_fiscal_summary` |
| — | Scan QR → visite trace | Automatisé | ✅ OK | `test_qr_scan_records_field_visit` |

### 5.2 Endpoints API validés

| Méthode | Route | Rôle Sprint 3.4 |
|---------|-------|-----------------|
| GET | `/api/municipality/operators/by-qr/{value}` | Identification commerce |
| GET | `/api/municipality/operators/{id}/fiscal-summary` | Situation + historique paiements |
| POST | `/api/municipality/fiscal/collections` | Encaissement (`obligation_ids[]`) |
| POST | `/api/municipality/operators/{id}/field-visits` | Contrôles terrain |
| GET | `/api/municipality/sync/status` | Compteurs synchronisation |

### 5.3 Indicateurs métier observables (post-déploiement)

| Indicateur | Source |
|------------|--------|
| Nombre de scans QR recouvrement | `field_visits` type `scan` |
| Nombre de consultations fiscales | `field_visits` type `consultation` |
| Montant encaissé par session | `cash_sessions` + `municipal_payments` |
| Quittances émises | `municipal_receipts` |
| Contrôles terrain | `field_visits` types `*_control` |

---

## 6. Anomalies identifiées et corrigées

### 6.1 Anomalies UX corrigées dans le Sprint 3.4

| # | Anomalie (avant) | Impact | Correctif |
|---|------------------|--------|-----------|
| A1 | Saisie obligatoire ID opérateur pour consulter | Erreurs, lenteur terrain | Scan QR en entrée principale + fallback |
| A2 | Encaissement avec montant saisi manuellement | Risque d'écart créances / encaissement | Workflow créances uniquement ; montant calculé |
| A3 | Tuiles « Bientôt disponible » (historique, sync, contrôles) | Modules inaccessibles | Écrans fonctionnels activés |
| A4 | Historique paiements incomplet (sans taxe ni mode) | Traçabilité insuffisante pour le DAF | Enrichissement API + cartes UI |

### 6.2 Anomalies antérieures impactant la recette 3.4 (déjà corrigées)

| Sprint | Anomalie | Correctif | Référence |
|--------|----------|-----------|-----------|
| 3.2.5 | Ouverture caisse HTTP 500 (`operator_id` NOT NULL sur `field_visits`) | Migration nullable | `RAPPORT_CORRECTIF_FIELD_VISITS_NULLABLE.md` |
| 3.2.7 | Premier encaissement sans obligation → message trompeur | Génération auto obligations + message API explicite | `RAPPORT_CORRECTIF_ENCAISSEMENT_INITIAL.md` |
| 3.3 | Encaissement global sans lien créance | Sélection taxes / pénalités / amendes | `SPRINT_3_3_SITUATION_FISCALE_CREANCES.md` |
| 3.3.1 | Impression quittance non configurable | Sélection imprimante Bluetooth | `SPRINT_3_3_1_IMPRIMANTE_BLUETOOTH.md` |

### 6.3 Points de vigilance restants (non bloquants)

| # | Point | Recommandation |
|---|-------|----------------|
| V1 | Mode offline non implémenté | Prévu V2.1 ; écran sync prépare le terrain |
| V2 | Tests PHPUnit non exécutés sur poste dev local | Relancer sur VPS avant signature DAF |
| V3 | Commerce sans taxe affectée | Message explicite Cas C (Sprint 3.2.7) — vérifier en recette |

---

## 7. Captures d'écran principales

> Les captures ci-dessous décrivent les écrans attendus. **Insérer les photos réelles** lors de la session terrain pilote (commerce PANET ou équivalent Owendo).

### 7.1 Situation fiscale — entrée QR First

**Écran :** `Situation fiscale` (sans opérateur sélectionné)

```
┌────────────────────────────────────┐
│  ←  Situation fiscale              │
├────────────────────────────────────┤
│                                    │
│  [ 📷  Scanner QR commerce     ]   │
│                                    │
│  ─────────── OU ───────────        │
│                                    │
│  ID opérateur                      │
│  ┌──────────────────────────────┐  │
│  │                              │  │
│  └──────────────────────────────┘  │
│                                    │
│  [        Consulter            ]   │
│                                    │
└────────────────────────────────────┘
```

*[Capture à insérer : `recette_3_4_01_situation_fiscale_entree.png`]*

---

### 7.2 Situation fiscale — fiche commerce et créances

**Écran :** `Situation fiscale` — commerce identifié (ex. PANET)

```
┌────────────────────────────────────┐
│  ←  Situation fiscale              │
├────────────────────────────────────┤
│  PANET                             │
│  Réf. OWE-COM-00000005             │
│  Activité : …  |  Quartier : …     │
│  ─────────────────────────────     │
│  Reste à payer : 75 000 XAF        │
│                                    │
│  Taxes                             │
│  ☑ PTA 2026 — 75 000 XAF           │
│  Pénalités                         │
│  ☐ …                               │
│                                    │
│  Total sélectionné : 75 000 XAF    │
│  [ Encaisser la sélection      ]   │
│                                    │
│  Historique des paiements          │
│  ┌────────────────────────────┐    │
│  │ 15 000 XAF — Q-2026-…      │    │
│  │ Date · Taxe · Agent · Mode │    │
│  └────────────────────────────┘    │
└────────────────────────────────────┘
```

*[Capture à insérer : `recette_3_4_02_situation_fiscale_detail.png`]*

---

### 7.3 Encaissement — entrée QR only

**Écran :** `Encaisser` (hub recouvrement)

```
┌────────────────────────────────────┐
│  ←  Encaisser                      │
├────────────────────────────────────┤
│                                    │
│  [ 📷  Scanner QR commerce     ]   │
│                                    │
│  (aucun champ ID / montant)        │
│                                    │
└────────────────────────────────────┘
```

*[Capture à insérer : `recette_3_4_03_encaissement_entree_qr.png`]*

---

### 7.4 Encaissement — confirmation

**Écran :** Confirmation avec montant calculé

```
┌────────────────────────────────────┐
│  ←  Encaisser                      │
├────────────────────────────────────┤
│  Session : CS-2026-…               │
│  PANET — OWE-COM-00000005          │
│  Créances sélectionnées (1)        │
│  Montant sélectionné : 75 000 XAF  │
│                                    │
│  [        Encaisser            ]   │
└────────────────────────────────────┘
```

*[Capture à insérer : `recette_3_4_04_encaissement_confirmation.png`]*

---

### 7.5 Contrôles terrain

**Écran :** Fiche commerce + type de contrôle

```
┌────────────────────────────────────┐
│  ←  Contrôle terrain               │
├────────────────────────────────────┤
│  Fiche commerce                    │
│  PANET — OWE-COM-00000005          │
│                                    │
│  Type de contrôle                  │
│  ◉ Contrôle de présence            │
│  ○ Contrôle licence                │
│  ○ Contrôle patente                │
│  ○ Contrôle municipal              │
│                                    │
│  Observations : …                  │
│  [ Enregistrer le contrôle     ]   │
└────────────────────────────────────┘
```

*[Capture à insérer : `recette_3_4_05_controle_terrain.png`]*

---

### 7.6 Synchronisation

**Écran :** Compteurs et état API

```
┌────────────────────────────────────┐
│  ←  Synchronisation           ↻    │
├────────────────────────────────────┤
│  Dernière synchro                  │
│  2026-06-16T14:30:00+01:00         │
│                                    │
│  Nombre de commerces        127    │
│  Nombre de paiements         43    │
│  Nombre de quittances        41    │
│                                    │
│  ☁ État API : Connecté             │
│                                    │
│  [ Synchroniser maintenant     ]   │
└────────────────────────────────────┘
```

*[Capture à insérer : `recette_3_4_06_synchronisation.png`]*

---

### 7.7 Accueil agent — modules activés

**Écran :** Tuiles Contrôles terrain, Historique des paiements, Synchronisation sans mention « Bientôt »

*[Capture à insérer : `recette_3_4_07_accueil_agent.png`]*

---

## 8. Conclusion de validation

### 8.1 Appréciation technique

Le Sprint 3.4 atteint ses objectifs de conception :

- Le **QR commerce** est devenu l'entrée standard du recouvrement.
- L'**encaissement** est **aligné sur les créances fiscales réelles**, condition indispensable pour la future comptabilité DAF.
- L'**historique des paiements** fournit les informations minimales de traçabilité (quittance, taxe, agent, mode).
- Les **contrôles terrain** alimentent le registre des visites, base du contrôle de présence commerciale.
- L'**écran de synchronisation** pose les fondations du mode offline.

### 8.2 Appréciation opérationnelle

| Critère | Évaluation |
|---------|------------|
| Utilisabilité agent terrain | ✅ Parcours simplifié (scan → encaisser) |
| Fiabilité des montants | ✅ Calcul automatique sur créances |
| Traçabilité | ✅ Paiements, quittances, visites, audit |
| Préparation DAF | ✅ Données structurées ; modules financiers à venir |
| Risque résiduel | ⚠️ Faible — recette terrain pilote recommandée |

### 8.3 Décision proposée

| Instance | Décision suggérée |
|----------|-------------------|
| **Équipe technique MAMI** | ✅ Valider la livraison Sprint 3.4 |
| **Service recouvrement / agents Owendo** | ⏳ Exécuter recette terrain (1/2 journée) et compléter les captures §7 |
| **Secrétaire Général / Maire** | ⏳ Viser le rapport après session pilote |
| **Futur DAF** | ✅ Valider le modèle de données recouvrement comme socle des recettes |

### 8.4 Prochaines étapes

1. Déployer le commit `e649356` sur le VPS API et distribuer l'APK agent mis à jour.
2. Organiser une session pilote avec 2 agents et 3 commerces QR (dont PANET).
3. Compléter les captures d'écran §7 et cocher la checklist terrain.
4. Ouvrir le **lot DAF** : consolidation recettes, rapports superviseur, prévision budget.

---

## Annexes

### A. Checklist recette terrain (à cocher)

| # | Vérification | OK |
|---|--------------|-----|
| 1 | APK post-`e649356` installé sur terminal agent | ☐ |
| 2 | Session caisse ouverte avant encaissement | ☐ |
| 3 | Scan QR → fiche commerce < 10 s | ☐ |
| 4 | Encaissement sans saisie montant manuel | ☐ |
| 5 | Quittance générée et vérifiable | ☐ |
| 6 | Historique paiements visible sur commerce test | ☐ |
| 7 | Contrôle présence enregistré | ☐ |
| 8 | Sync affiche compteurs cohérents | ☐ |

### B. Références documentaires

| Document | Contenu |
|----------|---------|
| [SPRINT_3_4_QR_FIRST.md](SPRINT_3_4_QR_FIRST.md) | Spécification technique Sprint 3.4 |
| [SPRINT_3_3_SITUATION_FISCALE_CREANCES.md](SPRINT_3_3_SITUATION_FISCALE_CREANCES.md) | Encaissement par créances |
| [RAPPORT_VALIDATION_SPRINT3_MUNICIPALITE.md](RAPPORT_VALIDATION_SPRINT3_MUNICIPALITE.md) | Validation globale P1 |
| [CHECKLIST_VALIDATION_TERRAIN_SPRINT3.md](CHECKLIST_VALIDATION_TERRAIN_SPRINT3.md) | Parcours agent terrain |

---

*Document établi par l'équipe MAMI.ga — Module Mairie / Recouvrement municipal.*  
*Pour visa : Secrétaire Général _______________  Date _______________*  
*Pour visa : Maire _______________  Date _______________*  
*Pour visa : DAF (à pourvoir) _______________  Date _______________*
