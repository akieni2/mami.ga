# MAMI Super App — Architecture Technique 2026-2028

**Version** : 1.0  
**Statut** : Implémenté (socle) — juin 2026  
**Principe** : une plateforme, zéro rupture Taxi

---

## 1. Vision

Transformer MAMI Taxi en **Super App multiservices** pour citoyens, chauffeurs, transporteurs, commerçants et agents municipaux — **sans réécriture** du module Taxi opérationnel.

| Principe | Décision |
|----------|----------|
| Backend | Un seul Laravel 13 |
| Base de données | Une seule MySQL |
| Mobile | Une app client (`mami_client`) — portail multiservices |
| Chauffeur | App dédiée `mami_driver` (inchangée Phase 1) |
| Auth | Sanctum + rôles centralisés |
| Extension | Modules + feature flags |

---

## 2. Structure backend

```
app/
├── Http/Controllers/Api/     # API Taxi historique (gel de compatibilité)
├── Models/                   # Modèles Taxi existants
├── Services/                 # Services Taxi existants
└── Modules/
    ├── Core/                 # Noyau Super App (rôles, enums, modèles communs)
    ├── Taxi/                 # Référence — code legacy non déplacé
    ├── Carpool/              # Covoiturage (scaffold + routes /api/carpool)
    ├── Transport/            # TM (scaffold + routes /api/transport)
    ├── Commerce/             # PME (scaffold + routes /api/commerce)
    └── Municipality/         # Mairie (scaffold + routes /api/municipality)
```

### Règle de compatibilité Taxi

- Les routes `POST /api/rides/*`, `GET /api/rides/*`, `POST /api/drivers/*` **ne changent pas**.
- Le module `Taxi` n'a pas déplacé le code — il documente et enregistre le module.
- Toute évolution Taxi continue dans `app/Services` jusqu'à migration progressive optionnelle.

---

## 3. Feature flags

Fichier : `config/mami.php` + variables `.env`

| Variable | Défaut | Effet |
|----------|--------|-------|
| `MAMI_SUPER_APP` | `true` | Active le mode Super App |
| `MAMI_MODULE_CARPOOL` | `false` | Module Covoiturage |
| `MAMI_MODULE_TRANSPORT` | `false` | Module Transport |
| `MAMI_MODULE_COMMERCE` | `false` | Module Commerce |
| `MAMI_MODULE_MUNICIPALITY` | `false` | Module Municipalité |
| `MAMI_TAXI_V2` | `false` | Taxi V2 (existant) |
| `MAMI_DISPATCH_V2` | `false` | Dispatch V2 (existant) |

**API mobile** : `GET /api/app/features` retourne `modules`, `super_app_enabled`, flags Taxi.

**Middleware** : `module:{slug}` → HTTP 403 si module désactivé.

---

## 4. Noyau commun (Core)

| Composant | Chemin |
|-----------|--------|
| Rôles enum | `App\Modules\Core\Enums\MamiRole` |
| Modules enum | `App\Modules\Core\Enums\MamiModule` |
| Modèles partagés | `App\Modules\Core\Models\*` |
| Morph map | `CoreModuleServiceProvider` |
| Features | `App\Support\MamiFeatures` |

Tables créées : voir `MAMI_DATABASE_MASTER_PLAN.md`.

---

## 5. Application mobile client

### Navigation (shell)

| Onglet | Route | Statut |
|--------|-------|--------|
| Accueil | `/` | Portail multiservices |
| Historique | `/history` | Taxi |
| Profil | `/profile` | Commun |

### Portail Accueil

- Grille de services : Taxi, Covoiturage, Transport, Commerce, Mairie
- Services désactivés : affichage grisé + message « Bientôt disponible »
- Taxi : navigation vers `/book` (flux existant 100 %)

Fichiers :
- `lib/features/home/presentation/screens/home_screen.dart`
- `lib/features/home/presentation/widgets/service_portal_grid.dart`
- `lib/core/config/mami_service_module.dart`
- `lib/core/config/app_features.dart`

---

## 6. Interface web admin

Inchangée Phase 1. Extensions futures par module dans le backoffice Blade existant.

---

## 7. Feuille de route alignée

| Phase | Contenu | Statut |
|-------|---------|--------|
| 0 | Socle Super App (modules, rôles, tables, portail) | **Fait** |
| 1 | Stabilisation Taxi | En cours |
| 2 | Paiements Mobile Money (`payments`, `transactions`) | Tables prêtes |
| 3 | Covoiturage — métier | Scaffold |
| 4 | Transport marchandises | Scaffold |
| 5 | Commerce & PME | Scaffold |
| 6 | Services municipaux | Scaffold |
| 7 | Portail citoyen | Partiel (app) |
| 8 | Plateforme nationale | — |

---

## 8. Instructions Cursor (obligatoires)

1. Vérifier que le module Taxi n'est pas impacté (tests `RideWorkflowTest`, `SuperAppArchitectureTest`).
2. Réutiliser `payments`, `ratings`, `locations`, `attachments`.
3. Ne jamais dupliquer GPS / paiements par module.
4. Nouveau module = `app/Modules/{Name}` + flag `MAMI_MODULE_*`.
5. Tests Feature obligatoires.
6. Documenter dans `MAMI_MODULES_SPECIFICATION.md`.
7. Rapport d'impact avant commit majeur.

---

## 9. Déploiement

```bash
php artisan migrate --force
php artisan db:seed --class=RolePermissionSeeder
php artisan config:clear
```

Variables recommandées production :

```env
MAMI_SUPER_APP=true
MAMI_TAXI_V2=true
MAMI_DISPATCH_V2=true
# Activer module par module :
# MAMI_MODULE_CARPOOL=true
```

---

## 10. Références

- `docs/MAMI_DATABASE_MASTER_PLAN.md`
- `docs/MAMI_MODULES_SPECIFICATION.md`
- `docs/MAMI_ROLE_PERMISSION_MATRIX.md`
- `docs/MAMI_TAXI_V2.md`
