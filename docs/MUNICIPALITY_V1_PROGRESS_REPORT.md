# MAMI Municipality V1 — Rapport d'avancement Phase 1

**Date** : 17 juin 2026  
**Périmètre** : Signalements citoyens géolocalisés (Owendo)  
**Branche** : `feature/mami-taxi-v2-p2`  
**Flag requis** : `MAMI_MODULE_MUNICIPALITY=true`

---

## 1. Tables créées

| Table | Description |
|-------|-------------|
| `municipal_territories` | Commune Owendo (`OWE`) |
| `municipal_sectors` | Arrondissements, 13 quartiers, 4 zones opérationnelles (ZOP) |
| `municipality_reports` | Signalements citoyens (`OWE-SIG-NNNNNN`) |
| `municipality_report_updates` | Historique des transitions de statut |

**Réutilisation Core** (aucune table photo dédiée) :
- `attachments` — photos (`purpose = report_photo`, morph `municipality_report`)
- `locations` — snapshot GPS à la création
- `audit_logs` — toutes les actions (`report.created`, `report.status_changed`)
- `notifications` — citoyen (reçu, pris en charge, résolu, clôturé)

**Migration** : `database/migrations/2026_06_18_100000_create_municipality_phase1_tables.php`  
**Seeder** : `database/seeders/OwendoTerritorySeeder.php`

---

## 2. API créées

Prefix : `/api/municipality` — middleware `auth:sanctum` + `module:municipality`

| Méthode | Endpoint | Rôle |
|---------|----------|------|
| POST | `/reports` | Créer signalement (+ photo multipart) |
| GET | `/reports` | Liste (citoyen = les siens ; agent = tous) |
| GET | `/reports/{id}` | Détail |
| POST | `/reports/{id}/assign` | Assigner à un agent |
| POST | `/reports/{id}/status` | Changer statut |
| GET | `/reports/map` | Couche GeoJSON `LayerSignalements` |
| GET | `/status` | Statut module |

**Statuts** : `new` → `assigned` → `in_progress` → `resolved` → `closed`

**Couleurs carte** :
- Rouge `#E53935` — Nouveau
- Orange `#FB8C00` — Assigné / En cours
- Vert `#43A047` — Résolu / Clôturé

---

## 3. Module backend

```
app/Modules/Municipality/
├── Enums/ReportCategory.php, ReportStatus.php
├── Models/ (MunicipalityReport, MunicipalityReportUpdate, MunicipalTerritory, MunicipalSector)
├── Services/ (ReportService, Repository, TerritorialResolver, LayerSignalements, Audit, ReferenceGenerator)
├── Http/Controllers/ (+ Admin)
├── Http/Requests/
├── Http/Resources/MunicipalityReportResource.php
├── Policies/MunicipalityReportPolicy.php
├── Events/ + Listeners/ (notifications citoyen)
└── Routes/api.php
```

**Config** : `config/municipality.php` (mapping quartier → ZOP)

**Permissions ajoutées** : `municipality.reports.create`, `municipality.reports.manage`

---

## 4. Backoffice web

| Route | Écran |
|-------|-------|
| `/admin/municipality/reports` | Liste + filtres (quartier, catégorie, statut, dates) |
| `/admin/municipality/reports/{id}` | Détail, assignation, changement statut |
| `/admin/municipality/map` | Carte SIG Leaflet — tous les signalements |
| `/admin/municipality/map/geojson` | Données GeoJSON (session admin) |

Navigation ajoutée dans la sidebar admin : **Signalements Owendo**, **Carte municipale**.

---

## 5. Écrans Flutter (`mami_client`)

| Route | Écran |
|-------|-------|
| `/municipality` | Portail Mairie |
| `/municipality/report/new` | Signaler (GPS auto, catégorie, photo, description) |
| `/municipality/reports` | Mes signalements |

**Fichiers** :
- `lib/features/municipality/data/municipality_repository.dart`
- `lib/features/municipality/presentation/screens/municipality_home_screen.dart`
- `lib/features/municipality/presentation/screens/create_municipality_report_screen.dart`
- `lib/features/municipality/presentation/screens/my_municipality_reports_screen.dart`

**Dépendance ajoutée** : `image_picker`

**Activation** : tuile « Mairie » sur l'accueil Super App quand `modules.municipality = true` dans `/api/app/features`.

---

## 6. Tests exécutés

```bash
php artisan test tests/Feature/Municipality tests/Feature/SuperAppArchitectureTest.php tests/Feature/RideWorkflowTest.php
```

| Résultat | Détail |
|----------|--------|
| **19 / 19 PASS** | 10 Municipality + 9 régression Super App / Taxi |

| Fichier | Scénarios |
|---------|-----------|
| `MunicipalityReportTest` | Création, référence `OWE-SIG-*`, liste, module désactivé |
| `MunicipalityMapTest` | GeoJSON, couleurs par statut |
| `MunicipalityWorkflowTest` | Workflow complet assign → closed, transitions invalides, **Taxi ride request OK** |

---

## 7. Carte SIG — capture

La carte municipale est disponible à `/admin/municipality/map` :

- Fond OpenStreetMap centré sur Owendo (Cité SNI)
- Marqueurs colorés par statut (légende en haut)
- Popup : référence, titre, catégorie, statut
- Source : `LayerSignalements` → GeoJSON FeatureCollection

> Capture d'écran à produire après déploiement avec `MAMI_MODULE_MUNICIPALITY=true` et signalements de démo.

---

## 8. Impacts et non-régressions

| Zone | Impact |
|------|--------|
| **Taxi** | Aucune modification tables/API `rides`, `drivers` |
| **Core** | Morph map `municipality_report` ; `AuthorizesRequests` sur `Controller` base |
| **Feature flag** | Municipality off par défaut — zéro impact prod sans activation |
| **MySQL** | Index nommé court sur `municipality_report_updates` (limite 64 car.) |

---

## 9. Activation

```env
MAMI_MODULE_MUNICIPALITY=true
```

```bash
php artisan migrate
php artisan db:seed --class=OwendoTerritorySeeder
php artisan storage:link
```

Client Flutter : activer le flag côté API `/app/features` ou build avec backend configuré.

---

## 10. Prochaines étapes (V2+)

- Module GIS transversal (`MAMI_MODULE_GIS`)
- Opérateurs économiques + zones économiques
- Recouvrement fiscal et brigades terrain
- Reverb temps réel sur la carte signalements

---

*Rapport généré avant commit Phase 1 — Municipality Signalements.*
