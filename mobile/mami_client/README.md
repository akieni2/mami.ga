# MAMI.GA — Application client (Sprint 01)

Application Flutter passager : connexion, réservation, suivi course, historique.

## Prérequis

- Flutter SDK 3.24+
- API prod : `https://api.mami.ga/api`

## Installation

```bash
cd mobile/mami_client
flutter create . --org ga.mami --project-name mami_client
flutter pub get
flutter run
```

L'URL API par défaut est `https://api.mami.ga/api` (voir `lib/core/config/app_config.dart`).

## Compte démo

| Email | Mot de passe |
|-------|----------------|
| `client@mami.ga` | `password` |

## Architecture

```
lib/
  core/       config, dio, router, theme, widgets
  features/
    auth/     login, register, session
    splash/   écran d'accueil + bootstrap session
    home/     carte + commander
    rides/    booking, searching, active, history
    profile/  profil + déconnexion
    location/ GPS utilisateur
    shell/    navigation principale
```

## Flux client

1. Splash → session ?
2. Login / Register
3. Home → Commander une course
4. Booking → saisie coords + prix estimé → `POST /rides/request`
5. Searching → polling `GET /rides/{id}` jusqu'à acceptation
6. Active ride → chauffeur, véhicule, statut, carte OSM
7. Historique → `GET /rides/history`

## Écrans

| # | Écran | Route |
|---|-------|-------|
| 1 | Splash | `/splash` |
| 2 | Login | `/login` |
| 3 | Register | `/register` |
| 4 | Home | `/` |
| 5 | Booking | `/book` |
| 6 | Searching | `/ride/searching/:id` |
| 7 | Active ride | `/ride/active/:id` |
| 8 | History | `/history` |
| 9 | Profile | `/profile` |

## Permissions GPS

**Android** — `AndroidManifest.xml` :

```xml
<uses-permission android:name="android.permission.ACCESS_FINE_LOCATION" />
<uses-permission android:name="android.permission.ACCESS_COARSE_LOCATION" />
```

**iOS** — `Info.plist` :

```xml
<key>NSLocationWhenInUseUsageDescription</key>
<string>Position requise pour commander une course.</string>
```
