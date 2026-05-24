# MAMI.GA — Application chauffeur (Flutter)

Application mobile Phase 3 pour les chauffeurs : authentification Sanctum, statut en ligne/hors ligne, GPS toutes les 10 s, réception et gestion des courses.

## Prérequis

- [Flutter SDK](https://docs.flutter.dev/get-started/install) 3.24+
- Backend Laravel en cours d'exécution (`php artisan serve`)
- Émulateur Android ou appareil physique

## Première installation

Depuis la racine du dépôt :

```bash
cd mobile/mami_driver
flutter create . --org ga.mami --project-name mami_driver
flutter pub get
```

`flutter create .` génère les dossiers `android/`, `ios/`, etc. sans écraser `lib/`.

### Permissions GPS

**Android** — `android/app/src/main/AndroidManifest.xml` :

```xml
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION" />
<uses-permission android:name="android.permission.ACCESS_COARSE_LOCATION" />
```

**iOS** — `ios/Runner/Info.plist` :

```xml
<key>NSLocationWhenInUseUsageDescription</key>
<string>Position requise pour le dispatch et le suivi des courses.</string>
```

## Lancer l'application

```bash
# Émulateur Android → API locale Laravel
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000/api

# Appareil physique (remplacer par l'IP du PC)
flutter run --dart-define=API_BASE_URL=http://192.168.1.10:8000/api

# Production
flutter run --dart-define=API_BASE_URL=https://votre-domaine.com/api
```

## Compte démo

| Email | Mot de passe |
|-------|----------------|
| `jean.driver@mami.ga` | `password` |

## Architecture

```
lib/
  core/          # Dio, thème, router, stockage token
  features/
    auth/        # Login, session Sanctum
    driver/      # Disponibilité online/offline
    location/    # GPS → POST /drivers/location/update
    rides/       # Course courante, historique, workflow
    home/        # Tableau de bord
    profile/     # Profil et déconnexion
```

- **Riverpod** — état global
- **Dio** — client HTTP
- **flutter_secure_storage** — token persistant
- **flutter_map** — OpenStreetMap (pas de Google Maps)

## Fonctionnalités

| Fonction | Endpoint API |
|----------|----------------|
| Connexion | `POST /api/login` |
| Session | `GET /api/me` |
| En ligne / hors ligne | `POST /api/drivers/availability` |
| GPS (10 s) | `POST /api/drivers/location/update` |
| Course active | `GET /api/rides/current` |
| Accepter / refuser | `POST /api/rides/{id}/accept`, `/reject` |
| Arrivé / démarrer / terminer | `/arrived`, `/start`, `/complete` |
| Historique | `GET /api/rides/history?as_driver=1` |

Le statut **occupé** est dérivé côté serveur lorsqu'une course est active.

## Build release

```bash
flutter build apk --dart-define=API_BASE_URL=https://votre-domaine.com/api
```
