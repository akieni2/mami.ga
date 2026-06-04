# MAMI.GA — Document technique (handoff développeur full stack)

**Version** : mai 2026  
**Dépôt** : https://github.com/akieni2/mami.ga  
**Public** : développeur full stack reprenant le projet

---

## 1. Vue d’ensemble

MAMI.GA est une plateforme de mobilité urbaine type taxi, orientée villes africaines (coordonnées démo autour de Libreville, Gabon : ~0.41°N, 9.47°E).

| Composant | Technologie | Statut |
|-----------|-------------|--------|
| API REST | Laravel 13, PHP 8.3+ | ✅ Phase 1 |
| Auth mobile | Laravel Sanctum (Bearer token) | ✅ |
| Auth admin | Session web (Breeze Blade) | ✅ |
| Base de données | **MySQL** (prod + tests) | ✅ |
| Temps réel (structure) | Laravel Broadcasting, événements | ✅ Phase 2 |
| Dashboard admin | Blade + Tailwind + Vite + Leaflet/OSM | ✅ |
| App chauffeur | Flutter (`mobile/mami_driver`) | ✅ Phase 3 |
| App client mobile | — | ❌ À faire |
| Firebase / WebSocket live | — | ❌ Préparé, non branché |
| Google Maps | — | ❌ OSM utilisé |
| Asterisk SIP / Mobile Money | — | ❌ Phases futures |

**Contraintes respectées à ce stade** : pas de Firebase, SIP, Mobile Money ni Google Maps dans le code livré.

---

## 2. Structure du dépôt

```
mami.ga/                          # Racine = application Laravel (pas de sous-dossier backend/)
├── app/
│   ├── Console/Commands/         # drivers:mark-offline
│   ├── Enums/                    # DriverStatus, RideStatus, RideEventType
│   ├── Events/                   # Broadcast (RideRequested, etc.)
│   ├── Http/
│   │   ├── Controllers/Api/      # Auth, Driver, Ride
│   │   ├── Controllers/Admin/    # Dashboard, live data, carte
│   │   ├── Controllers/Auth/     # Breeze (session admin)
│   │   ├── Middleware/           # ForceJsonResponse, EnsureUserIsAdmin
│   │   ├── Requests/
│   │   └── Resources/            # DriverResource, RideResource
│   ├── Models/
│   ├── Services/                 # Logique métier
│   └── Support/                  # ApiResponse, GeoDistance
├── config/mami.php               # Paramètres métier
├── database/migrations/
├── database/seeders/
├── mobile/mami_driver/           # App Flutter chauffeur
├── resources/views/admin/        # Vues admin
├── resources/js/admin.js         # Polling live 10 s
├── routes/api.php
├── routes/web.php
├── routes/auth.php
├── routes/channels.php
├── tests/                        # PHPUnit Feature (MySQL only)
└── docs/                         # Ce document
```

> **Note** : un dossier `backend/` peut exister par erreur d’anciennes manipulations ; la source de vérité est la **racine** du repo.

---

## 3. Chronologie des phases (ce qui a été fait)

### Phase 1 — MVP Backend API

**Objectif** : API taxi complète avec auth, chauffeurs, véhicules, courses, dispatch GPS.

**Livrables** :
- Modèles : `User`, `Driver`, `Vehicle`, `Ride`, `DriverLocation`
- Enums : `DriverStatus` (`offline`, `online`, `on_ride`), `RideStatus` (`pending` → `completed` / `cancelled`)
- Services :
  - `RideDispatchService` — demande course, acceptation, arrivée, démarrage, fin, rejet
  - `DriverLocationService` — mise à jour GPS, recherche chauffeurs proches (Haversine)
- Contrôleurs API + enveloppe JSON standard (`App\Support\ApiResponse`)
- Sanctum : `POST /api/register`, `/login`, `/logout`, `GET /api/me`
- Middleware API : `ForceJsonResponse` (réponses JSON cohérentes)
- Seeders : client démo, 3 chauffeurs, véhicules, admin
- Tests Feature : auth, workflow course

### Phase 2 — Temps réel & tracking

**Objectif** : structure broadcast Firebase-friendly, présence chauffeur, audit, ETA.

**Livrables** :
- Table `ride_events` + `RideEventRecorder`
- Événements Laravel : `RideRequested`, `RideAccepted`, `DriverArrived`, `RideStarted`, `RideCompleted`, `DriverLocationUpdated`
- Trait `BroadcastsMamiRealtime` — payload normalisé `{ event, payload, occurred_at }`
- Canaux : `mami.rides.{rideId}`, `mami.drivers.{driverId}` (`routes/channels.php`)
- `DriverPresenceService` — présence `online` / `busy` / `offline` selon course active + `last_seen_at`
- `RideTrackingService`, `DistanceRefreshService` (distance + ETA)
- Champ `drivers.last_seen_at`
- Commande planifiée : `php artisan drivers:mark-offline` (chaque minute)
- Config : `config/mami.php` + variables `.env` `MAMI_*`
- Tests : présence, tracking, événements temps réel

**Broadcast actuel** : `BROADCAST_CONNECTION=log` dans `.env.example` — les événements sont émis mais pas poussés vers un broker externe tant que Firebase/Pusher n’est pas configuré.

### Dashboard admin (Blade)

**Objectif** : monitoring opérationnel sans WebSocket (polling 10 s).

**Livrables** :
- Auth session : `routes/auth.php`, `AuthenticatedSessionController`, `LoginRequest` (connexion **admin uniquement** : `is_admin = true`)
- Middleware `admin` → `EnsureUserIsAdmin`
- Pages : `/dashboard`, `/drivers`, `/rides`, `/map`
- API interne live : `/admin/live/dashboard`, `/drivers`, `/map`
- Front : Tailwind, Vite (`resources/js/admin.js`), carte **Leaflet + OpenStreetMap**
- Colonne `users.is_admin`
- Tests : `AdminDashboardTest`, `AdminLiveDataTest`

### Phase 3 — Application Flutter chauffeur

**Objectif** : première app mobile pour les chauffeurs.

**Livrables** (`mobile/mami_driver/`) :
- Architecture : `core/` + `features/` (clean architecture light)
- **Dio** + **Riverpod** + **go_router**
- **flutter_secure_storage** — token Sanctum persistant
- **geolocator** — GPS envoyé toutes les **10 secondes** vers `POST /api/drivers/location/update`
- Polling course : `GET /api/rides/current` (~8 s)
- Écrans : Login, Accueil, Course active, Historique, Profil
- Carte : **flutter_map** + tuiles OSM (pas Google Maps)
- Endpoints API ajoutés pour le mobile : `GET /api/rides/current`, `POST /api/rides/{id}/reject`

### Publication Git

- Remote : `https://github.com/akieni2/mami.ga.git`, branche `main`
- Historique en **commits atomiques** (feat/fix/test/docs par domaine)
- Merge effectué avec stubs Breeze distants sur GitHub (conserver version locale admin pour `routes/web.php`, seeders, login)

---

## 4. Installation environnement de développement

### 4.1 Prérequis

| Outil | Version recommandée |
|-------|---------------------|
| PHP | 8.3+ avec extensions : `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath` |
| Composer | 2.x |
| MySQL | 8.x |
| Node.js | 20+ (pour Vite / admin) |
| Flutter | 3.24+ (app chauffeur) |

### 4.2 Backend Laravel (racine du repo)

```bash
git clone https://github.com/akieni2/mami.ga.git
cd mami.ga

composer install
cp .env.example .env
php artisan key:generate

# Créer la base MySQL
mysql -u root -p -e "CREATE DATABASE mami_ga CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Éditer .env : DB_DATABASE, DB_USERNAME, DB_PASSWORD

php artisan migrate --seed
php artisan serve
```

API disponible : `http://127.0.0.1:8000/api`

### 4.3 Frontend admin (assets compilés)

```bash
npm install
npm run build    # production
# ou
npm run dev      # développement avec HMR
```

Admin : `http://127.0.0.1:8000/login` → redirection `/dashboard` si admin.

### 4.4 App Flutter chauffeur

```bash
cd mobile/mami_driver

# Si dossiers android/ios absents ou incomplets :
flutter create . --org ga.mami --project-name mami_driver

flutter pub get

# Émulateur Android → machine hôte Laravel
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api

# Appareil physique (IP LAN du PC)
flutter run --dart-define=API_BASE_URL=http://192.168.x.x:8000/api
```

**Permissions GPS** (à vérifier après `flutter create`) :
- Android : `ACCESS_FINE_LOCATION`, `ACCESS_COARSE_LOCATION` dans `AndroidManifest.xml`
- iOS : `NSLocationWhenInUseUsageDescription` dans `Info.plist`

---

## 5. Configuration (.env)

### 5.1 Variables principales

```env
APP_NAME=MAMI.GA
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mami_ga
DB_USERNAME=root
DB_PASSWORD=

SESSION_DRIVER=database          # Sessions admin web
BROADCAST_CONNECTION=log         # log | pusher | etc. — pas Firebase encore
QUEUE_CONNECTION=database

SANCTUM_STATEFUL_DOMAINS=localhost,127.0.0.1
```

### 5.2 Paramètres métier MAMI (`config/mami.php`)

| Variable `.env` | Défaut | Rôle |
|-----------------|--------|------|
| `MAMI_DRIVER_SEARCH_RADIUS_KM` | 10 | Rayon recherche chauffeur à la demande |
| `MAMI_RIDE_BASE_PRICE` | 500 | Tarif de base (FCFA) |
| `MAMI_RIDE_PRICE_PER_KM` | 250 | Prix au km |
| `MAMI_BROADCAST_PREFIX` | mami | Préfixe canaux broadcast |
| `MAMI_DRIVER_OFFLINE_THRESHOLD_SECONDS` | 300 | Absence heartbeat → offline |
| `MAMI_ETA_AVERAGE_SPEED_KMH` | 25 | ETA simplifié |

### 5.3 Tests PHPUnit (MySQL obligatoire)

Les tests **n’utilisent pas SQLite**. Le fichier `tests/bootstrap.php` force une base dédiée :

```env
DB_TEST_DATABASE=mami_ga_testing
# DB_TEST_HOST=127.0.0.1
# DB_TEST_USERNAME=
# DB_TEST_PASSWORD=
```

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS mami_ga_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php artisan test
```

---

## 6. Base de données

### 6.1 Schéma logique

```
users
  ├── drivers (1:1 user_id)
  │     ├── vehicles (1:1 driver_id)
  │     ├── driver_locations (historique GPS)
  │     └── rides (driver_id)
  └── rides (client_id)

ride_events (audit par course)
personal_access_tokens (Sanctum)
sessions (admin web)
```

### 6.2 Migrations (ordre indicatif)

| Fichier | Contenu |
|---------|---------|
| `0001_01_01_000000_create_users_table` | users, password reset |
| `2026_05_23_000000_add_phone_to_users_table` | téléphone |
| `2026_05_25_000001_add_is_admin_to_users_table` | flag admin |
| `2026_05_23_000001_create_drivers_table` | chauffeurs + GPS + status |
| `2026_05_23_000002_create_vehicles_table` | véhicules |
| `2026_05_23_000003_create_rides_table` | courses |
| `2026_05_23_000004_create_driver_locations_table` | historique positions |
| `2026_05_24_000001` / `165946` | `last_seen_at` sur drivers (doublon possible — à consolider si migration échoue) |
| `2026_05_24_000002_create_ride_events_table` | audit événements |
| `0001_01_01_000003_create_personal_access_tokens_table` | Sanctum |

### 6.3 Comptes seed (mot de passe : `password`)

| Rôle | Email | Notes |
|------|-------|-------|
| Admin | `admin@mami.ga` | `is_admin = true` |
| Client | `client@mami.ga` | demande de courses |
| Chauffeur 1 | `jean.driver@mami.ga` | GPS ~0.4162, 9.4673 |
| Chauffeur 2 | `paul.driver@mami.ga` | |
| Chauffeur 3 | `marc.driver@mami.ga` | |

`DatabaseSeeder` appelle : `AdminSeeder`, `DriverSeeder`, `VehicleSeeder`, `AdminSeeder` (doublon AdminSeeder — sans impact fonctionnel, nettoyage possible).

---

## 7. API REST — référence complète

**Base URL** : `/api`  
**Headers** : `Accept: application/json`, `Content-Type: application/json`  
**Auth** (routes protégées) : `Authorization: Bearer {token}`

### 7.1 Format de réponse

Succès :

```json
{
  "success": true,
  "message": "OK",
  "data": { }
}
```

Erreur :

```json
{
  "success": false,
  "message": "Description de l'erreur",
  "data": null
}
```

Pagination (`GET /api/rides/history`) :

```json
{
  "success": true,
  "message": "...",
  "data": [ ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 5
  }
}
```

### 7.2 Authentification

| Méthode | Route | Body / notes |
|---------|-------|----------------|
| POST | `/register` | `name`, `email`, `password`, `password_confirmation`, `phone?` |
| POST | `/login` | `email`, `password` → `{ user, token }` ; login charge `driver.vehicle` |
| POST | `/logout` | token courant révoqué |
| GET | `/me` | `{ user }` avec `is_driver`, `driver` |

### 7.3 Chauffeurs

| Méthode | Route | Body / notes |
|---------|-------|----------------|
| GET | `/drivers/nearby` | Query : `latitude`, `longitude`, `radius_km?` |
| POST | `/drivers/location/update` | `latitude`, `longitude` — met à jour présence + historique |
| POST | `/drivers/availability` | `is_available` (bool) — online/offline manuel |
| GET | `/drivers/{id}/live-location` | Client assigné ou le chauffeur lui-même |

### 7.4 Courses

| Méthode | Route | Acteur | Description |
|---------|-------|--------|-------------|
| POST | `/rides/request` | Client | Dispatch chauffeur le plus proche → `pending` |
| GET | `/rides/current` | Chauffeur | Course active + `distance_to_pickup_km` si GPS |
| GET | `/rides/history` | Tous | `?as_driver=1` pour historique chauffeur |
| GET | `/rides/{id}` | Client/Chauffeur | Détail |
| GET | `/rides/{id}/tracking` | Client/Chauffeur | Snapshot tracking |
| POST | `/rides/{id}/accept` | Chauffeur | `pending` → `accepted` |
| POST | `/rides/{id}/reject` | Chauffeur | `pending` → `cancelled`, libère chauffeur |
| POST | `/rides/{id}/arrived` | Chauffeur | → `arrived` |
| POST | `/rides/{id}/start` | Chauffeur | → `started` |
| POST | `/rides/{id}/complete` | Chauffeur | → `completed` |

### 7.5 Cycle de vie d’une course

```
[Client] POST /rides/request
    → status: pending, driver_id assigné, chauffeur is_available=false

[Chauffeur] POST /accept  → accepted
[Chauffeur] POST /arrived → arrived
[Chauffeur] POST /start   → started
[Chauffeur] POST /complete → completed, chauffeur redevenu disponible

[Chauffeur] POST /reject (si pending) → cancelled, driver_id=null
```

**Présence chauffeur** (calculée, pas un toggle API) :
- `online` : `is_available` + `last_seen_at` récent
- `busy` : course active (pending, accepted, arrived, started)
- `offline` : seuil dépassé ou indisponible

---

## 8. Couche services (logique métier)

| Service | Responsabilité |
|---------|----------------|
| `RideDispatchService` | CRUD cycle course, dispatch, reject, events broadcast |
| `DriverLocationService` | Update GPS, nearby search, enregistrement `driver_locations` |
| `DriverPresenceService` | Résolution online/busy/offline, `markStaleDriversOffline` |
| `RideTrackingService` | Snapshot / live location pour une course |
| `DistanceRefreshService` | Distance Haversine + ETA |
| `RideEventRecorder` | Persistance `ride_events` |
| `AdminDashboardService` | Stats pour dashboard Blade |
| `AdminLiveMapService` | Positions pour carte admin |

**Calcul prix** (à la demande) : base + (distance pickup→destination × prix/km), via `GeoDistance` et config `mami.php`.

---

## 9. Dashboard admin (web)

### 9.1 Routes (`routes/web.php`)

| URL | Contrôleur | Middleware |
|-----|------------|------------|
| `/` | redirect login ou dashboard | — |
| `/login` | Breeze `AuthenticatedSessionController` | guest |
| `/dashboard` | `Admin\DashboardController` | auth, admin |
| `/drivers` | `Admin\DriverController` | auth, admin |
| `/rides` | `Admin\RideController` | auth, admin |
| `/map` | `Admin\LiveMapController` | auth, admin |
| `/admin/live/*` | `Admin\LiveDataController` | auth, admin |

### 9.2 Polling JavaScript

Fichier : `resources/js/admin.js` — intervalle **10 secondes** :
- `GET /admin/live/dashboard`
- `GET /admin/live/drivers`
- `GET /admin/live/map`

Entrée Vite : `vite.config.js` → `resources/js/admin.js` en plus de `app.js`.

### 9.3 Sécurité admin

- `LoginRequest` : refuse les utilisateurs sans `is_admin`
- `EnsureUserIsAdmin` : middleware `admin`
- Redirection invités → `route('login')`, utilisateurs connectés → `route('admin.dashboard')` (`bootstrap/app.php`)

---

## 10. Application Flutter chauffeur

### 10.1 Stack

| Package | Usage |
|---------|--------|
| `dio` | HTTP + intercepteur Bearer |
| `flutter_riverpod` | État global |
| `flutter_secure_storage` | Token Sanctum |
| `geolocator` | GPS device |
| `go_router` | Navigation + guard auth |
| `flutter_map` + `latlong2` | Carte OSM |

### 10.2 Configuration build

Fichier : `lib/core/config/app_config.dart`

```dart
API_BASE_URL  // --dart-define, défaut http://10.0.2.2:8000/api
gpsInterval   // 10 secondes
ridePollInterval // 8 secondes
```

### 10.3 Architecture `lib/`

```
core/
  config/     app_config.dart
  network/    api_client.dart, api_exception.dart
  storage/    token_storage.dart
  theme/      app_theme.dart (clair/sombre)
  router/     app_router.dart
  widgets/    PrimaryButton, StatusChip, RideMap

features/
  auth/       login, restoreSession, logout
  driver/     setOnline, updateLocation (repository)
  location/   LocationTrackerNotifier (timer 10s)
  rides/      current, history, accept/reject/arrived/start/complete
  home/       dashboard chauffeur
  profile/    profil + themeModeProvider
  shell/      bottom navigation
```

### 10.4 Flux chauffeur typique

1. Login → token stocké → `GET /me`
2. Accueil → bascule **En ligne** → `POST /drivers/availability` + démarrage tracker GPS
3. Polling détecte `GET /rides/current` avec `status: pending` → carte + Accepter/Refuser
4. Accept → écran course active → Arrivé → Démarrer → Terminer
5. Historique → `GET /rides/history?as_driver=1`

---

## 11. Tests automatisés

| Fichier | Couverture |
|---------|------------|
| `AuthTest.php` | register, login, me, logout |
| `RideWorkflowTest.php` | request → accept → start → complete |
| `DriverPresenceTest.php` | online/busy/offline |
| `RideTrackingTest.php` | tracking API |
| `RealtimeEventsTest.php` | événements broadcast |
| `DriverRideApiTest.php` | current, reject |
| `AdminDashboardTest.php` | pages admin |
| `AdminLiveDataTest.php` | endpoints live JSON |
| `ProfileTest.php` | Breeze profile (si utilisé) |

Commande : `php artisan test` (nécessite MySQL + base `mami_ga_testing`).

---

## 12. Déploiement production (VPS Ubuntu — checklist)

```bash
# Sur le serveur
git pull origin main
composer install --no-dev --optimize-autoloader
cp .env.example .env   # puis éditer pour prod
php artisan key:generate
php artisan migrate --force
php artisan config:cache
php artisan route:cache
npm ci && npm run build

# Scheduler (cron Laravel)
* * * * * cd /chemin/mami.ga && php artisan schedule:run >> /dev/null 2>&1
```

**Serveur web** : Nginx/Apache, document root → `public/`, PHP-FPM 8.3+.

**MySQL** : base dédiée, utilisateur avec droits limités.

**HTTPS** : obligatoire en prod ; mettre à jour `APP_URL`, `SANCTUM_STATEFUL_DOMAINS` si cookies SPA plus tard.

**Flutter** : build APK/IPA avec `API_BASE_URL=https://votre-domaine.com/api`.

**Extensions PHP VPS** (erreur fréquente) : `pdo_mysql` requis — **ne pas** compter sur SQLite pour les tests en prod/CI si vous gardez `tests/bootstrap.php` actuel.

---

## 13. Dépôt Git & conventions

- **Remote** : `https://github.com/akieni2/mami.ga.git`
- **Branche principale** : `main`
- **Style de commits** (observé) :
  - `feat(api): ...`
  - `feat(admin): ...`
  - `feat(flutter): ...`
  - `feat(realtime): ...`
  - `test(...): ...`
  - `fix(test): ...`
  - `docs: ...`

Commits atomiques par domaine (API, admin, Flutter, tests, docs séparés).

---

## 14. Points d’attention / dette technique

1. **Double migration `last_seen_at`** : deux fichiers migrations similaires — vérifier sur une base neuve ; supprimer le doublon si conflit.
2. **`AdminSeeder` appelé deux fois** dans `DatabaseSeeder` — cosmétique.
3. **`composer.json`** : entrée `laravel/breeze` dupliquée dans `require-dev` — à nettoyer.
4. **Broadcast** : configuré en `log` ; branchement Firebase/Pusher = travail Phase 2 bis.
5. **App Flutter** : pas de push notification — polling uniquement.
6. **Rejet course** : annule la course (`cancelled`) ; pas de ré-assignation automatique à un autre chauffeur.
7. **Dossier `backend/`** : ignorer ou supprimer s’il n’est pas synchronisé avec la racine.
8. **Tests** : MySQL uniquement — CI doit provisionner `mami_ga_testing`.

---

## 15. Roadmap (non implémenté)

| Phase | Contenu |
|-------|---------|
| 3b | App Flutter **client** (demande course, suivi) |
| 2 bis | Firebase Realtime / FCM pour remplacer le polling |
| 4 | Asterisk SIP (appels masqués) |
| 5 | Mobile Money (Airtel Money, Moov, etc.) |
| — | Google Maps (optionnel, OSM suffit pour MVP) |

---

## 16. Contacts & ressources utiles

- README opérationnel : `/README.md`
- README Flutter : `/mobile/mami_driver/README.md`
- Config métier : `/config/mami.php`
- Routes API : `/routes/api.php`

---

*Document généré pour handoff technique — à mettre à jour à chaque phase majeure.*
