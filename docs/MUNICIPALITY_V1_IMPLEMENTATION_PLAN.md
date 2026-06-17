# MAMI Municipality V1 — Plan d'implémentation

**Version** : 1.0 (document de conception — **aucun code**)  
**Date** : juin 2026  
**Périmètre** : SIG Owendo V1  
**Prérequis** : Super App socle déployé (`d12d49a+`)

---

## 1. Objectifs V1

| # | Objectif | Livrable |
|---|----------|----------|
| O1 | Signalements citoyens géolocalisés | API + mobile + carte |
| O2 | Registre opérateurs économiques | `OWE-COM-XXXXXX` + carte |
| O3 | Statuts fiscaux colorés | Calcul + carte |
| O4 | Dashboard recouvrement | KPIs + zones prioritaires |
| O5 | Brigades terrain | Interventions GPS + photos |
| O6 | Tableau de bord maire | Vue consolidée |
| O7 | Carte SIG centrale web | 5 couches activables |

---

## 2. Phases et jalons

### Phase 0 — Validation documents (en cours)

| Jalon | Durée | Livrable |
|-------|-------|----------|
| P0.1 | 1 semaine | Validation documents architecture + référentiel territorial |
| P0.2 | 3 jours | Référentiel quartiers Owendo (CSV/JSON) |
| P0.3 | 2 jours | Règles fiscales validées par la commune |
| P0.4 | 1 jour | Go/No-Go direction |

**Critère sortie** : signature validation + issue GitHub « Municipality V1 — GO »

---

### Phase 1 — Fondations (2 semaines)

| Tâche | Module | Détail |
|-------|--------|--------|
| Flags `MAMI_MODULE_GIS` | Core | `config/mami.php`, `MamiFeatures`, middleware |
| Migrations M1–M2 | Municipality | `municipal_territories`, `municipal_sectors`, **`economic_zones`**, opérateurs |
| Permissions | Core | Seeder permissions GIS/fiscal/brigade |
| Rôles brigade | Core | `field_team_leader`, `field_agent`, `fiscal_officer` |
| Module GIS scaffold | GIS | `GisModuleServiceProvider`, routes vides |
| Tests migration | — | Aucune table Taxi altérée |

**Critère sortie** : `php artisan migrate` + tests verts

---

### Phase 2 — Signalements citoyens (2 semaines)

| Tâche | Détail |
|-------|--------|
| Migration `citizen_reports` | |
| `CitizenReportService` | Machine à états |
| API CRUD + transition | §4 GIS_API_SPECIFICATION |
| Events Reverb | `CitizenReportCreated`, `CitizenReportStatusChanged` |
| Mobile citoyen | Écran signalement + photo + suivi statut |
| Couche carte | Intégration `GET /gis/map` |
| Tests Feature | Cycle complet citoyen → agent → résolu |

**Machine à états signalements** :

```
nouveau → assigne → en_cours → resolu → cloture
         ↘___________________↗
```

---

### Phase 3 — Recensement économique (2 semaines)

| Tâche | Détail |
|-------|--------|
| Migration opérateurs + tax_status | |
| `EconomicRegistryService` | Génération `OWE-COM-NNNNNN` |
| `TaxStatusResolverService` | vert/orange/rouge/noir |
| API opérateurs | CRUD + historique fiscal |
| Import CSV initial | Opérateurs existants Owendo |
| Couche carte opérateurs | Filtres catégorie / fiscal |
| Web admin | CRUD opérateurs |

---

### Phase 4 — Recouvrement fiscal (2 semaines)

Voir `FISCAL_RECOVERY_MODULE_SPEC.md`

| Tâche | Détail |
|-------|--------|
| Migrations campagnes + visites + revenus | |
| Dashboard recouvrement | API + web |
| Carte zones prioritaires | Heatmap / clusters par secteur |
| Liaison opérateurs en retard | |

---

### Phase 5 — Brigades terrain (2 semaines)

| Tâche | Détail |
|-------|--------|
| Migrations équipes + interventions | |
| API interventions | GPS validation, photos |
| Vue brigade mobile | Liste tâches du jour |
| Liaison signalements + visites fiscales | |
| Audit complet | `audit_logs` + `locations` |

---

### Phase 6 — GIS & Dashboard Maire (2 semaines)

| Tâche | Détail |
|-------|--------|
| Migrations `gis_layers`, `gis_features` | |
| Seed 5 couches + équipements | |
| `GisLayerAggregatorService` | `GET /gis/map` |
| Carte web admin `/admin/gis` | Leaflet, filtres, légende |
| Dashboard maire | `GET /municipality/mayor/dashboard` |
| Couche transport (lecture `drivers`) | |
| Tests E2E carte | |

---

### Phase 7 — Recette & déploiement (1 semaine)

| Tâche | Détail |
|-------|--------|
| Tests charge carte (bbox) | |
| Formation agents municipaux | |
| Import données réelles Owendo | |
| Activation progressive prod | `MAMI_MODULE_MUNICIPALITY=true` puis `GIS=true` |
| APK client | Tuile Mairie active |

---

## 3. Calendrier indicatif

| Phase | Semaines | Cumul |
|-------|----------|-------|
| P0 Validation | 1–2 | S2 |
| P1 Fondations | 2 | S4 |
| P2 Signalements | 2 | S6 |
| P3 Recensement | 2 | S8 |
| P4 Recouvrement | 2 | S10 |
| P5 Brigades | 2 | S12 |
| P6 GIS + Maire | 2 | S14 |
| P7 Recette | 1 | **S15** |

**Durée totale estimée** : ~15 semaines après Go validation.

---

## 4. Structure code à créer (post-validation)

```
app/Modules/GIS/
├── GisModuleServiceProvider.php
├── Http/Controllers/
│   ├── GisMapController.php
│   ├── GisLayerController.php
│   └── GisSearchController.php
├── Services/
│   ├── GisLayerAggregatorService.php
│   └── TerritorialSearchService.php
├── Repositories/
└── Tests/

app/Modules/Municipality/
├── MunicipalityModuleServiceProvider.php (étendre)
├── CitizenReports/
├── EconomicRegistry/
├── FiscalRecovery/
├── FieldOperations/
└── MayorDashboard/
```

---

## 5. Tests obligatoires par phase

| Phase | Tests |
|-------|-------|
| P1 | `GisMigrationTest`, `TaxiCompatibilityTest` |
| P2 | `CitizenReportWorkflowTest`, `CitizenReportBroadcastTest` |
| P3 | `EconomicOperatorRegistryTest`, `TaxStatusResolverTest` |
| P4 | `RecoveryCampaignTest`, `RecoveryDashboardTest` |
| P5 | `FieldInterventionGpsTest`, `FieldInterventionAttachmentTest` |
| P6 | `GisMapAggregatorTest`, `MayorDashboardTest`, `TransportLayerReadOnlyTest` |

---

## 6. Risques et mitigations

| Risque | Mitigation |
|--------|------------|
| Données quartiers incomplètes | Import progressif + champ texte `quartier` |
| Performance carte | Bbox obligatoire, clustering, pagination |
| Résistance terrain brigades | V1 formulaire simple, mode offline V1.1 |
| Conflit module Commerce | Opérateurs économiques autonomes V1 ; lien V2 |
| Régression Taxi | Tests ride workflow à chaque PR + lecture seule drivers |

---

## 7. Dépendances externes

| Dépendance | Responsable | Échéance |
|------------|-------------|----------|
| Liste quartiers/secteurs Owendo | Commune | Avant P1 |
| Grille catégories économiques | Commune / DGI | Avant P3 |
| Seuils fiscaux 90 jours | Direction financière | Avant P4 |
| Comptes agents municipaux | IT commune | Avant P7 |

---

## 8. Critères d'acceptation V1

- [ ] Citoyen crée signalement avec GPS depuis Super App  
- [ ] Agent voit signalement sur carte < 5 s après création (Reverb)  
- [ ] 100 opérateurs importés avec `OWE-COM-` et statut fiscal  
- [ ] Dashboard recouvrement affiche montants et zones prioritaires  
- [ ] Brigade soumet intervention avec photo + GPS validé  
- [ ] Maire voit KPIs + carte temps réel web  
- [ ] Couche taxis visible sans altération API Taxi  
- [ ] Zéro régression tests Taxi existants  

---

## 9. Prochaine action immédiate

**Attendre validation des 5 documents** par la Direction du Projet MAMI avant toute ligne de code.

Documents à valider :
1. `GIS_ARCHITECTURE.md`
2. `GIS_DATABASE_DESIGN.md`
3. `GIS_API_SPECIFICATION.md`
4. `MUNICIPALITY_V1_IMPLEMENTATION_PLAN.md` (ce document)
5. `FISCAL_RECOVERY_MODULE_SPEC.md`
6. `MUNICIPAL_TERRITORIAL_REFERENCE.md`

---

## 10. Schéma d'évolution produit (V1 → V7)

Aligné sur `GIS_ARCHITECTURE.md` §13 et `MUNICIPAL_TERRITORIAL_REFERENCE.md` §14.

| Version | Focus | Livrable clé |
|---------|-------|--------------|
| **V1** | Signalements | Mobile + carte + workflow agent |
| **V2** | Opérateurs économiques | Registre `OWE-COM-*` + `economic_zones` |
| **V3** | Fiscalité | Dashboard recouvrement + statuts colorés |
| **V4** | Brigades | Interventions GPS terrain |
| **V5** | Cadastre | `land_parcels`, `parcel_reference` — sans refonte V1–V4 |
| **V6** | Urbanisme | Permis, zonage, occupations |
| **V7** | Aide à la décision | BI maire, scénarios, analytics |

Le plan d'implémentation détaillé §2 (phases P1–P7) couvre **V1 à V4** ; V5–V7 feront l'objet de documents dédiés après mise en production V4.

---

*Plan d'implémentation — juin 2026*
