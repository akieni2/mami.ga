# MAMI.GA

Plateforme intelligente de mobilité urbaine pour l’Afrique — réservation taxi, GPS, dispatch et suivi temps réel.

## Technologies

- Laravel 13 (API)
- Sanctum (authentification token)
- Laravel Broadcasting (structure temps réel, compatible Firebase)
- MySQL
- Flutter, Google Maps, Asterisk SIP, Mobile Money (phases futures)

## État du projet

| Phase | Statut | Contenu |
|-------|--------|---------|
| **1 — MVP Backend** | Terminé | API REST, auth, dispatch |
| **2 — Temps réel** | En cours | GPS live, tracking, événements |
| 3 — Mobile | À venir | Flutter client + chauffeur |
| 4 — VoIP | À venir | Asterisk SIP |
| 5 — Paiements | À venir | Mobile Money |

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

API : `http://127.0.0.1:8000/api`

## Architecture temps réel (Phase 2)

Le backend utilise **Laravel Broadcasting** avec des événements structurés pour une intégration Firebase future, sans WebSocket custom au MVP.

### Canaux (préfixe `mami` par défaut)

| Canal | Usage |
|-------|--------|
| `mami.rides.{rideId}` | Suivi course (client + chauffeur assigné) |
| `mami.drivers.{driverId}` | Position live chauffeur |

### Événements broadcast

- `RideRequested`, `RideAccepted`, `DriverArrived`, `RideStarted`, `RideCompleted`
- `DriverLocationUpdated` (heartbeat GPS)

Chaque payload inclut un enveloppe Firebase-friendly :

```json
{
  "event": "RideAccepted",
  "payload": { "ride_id": 1, "status": "accepted" },
  "occurred_at": "2026-05-24T12:00:00+00:00"
}
```

### Présence chauffeur

| État | Logique |
|------|---------|
| `online` | GPS récent + disponible |
| `busy` | Course active (pending → started) |
| `offline` | Pas de heartbeat (`last_seen_at` > seuil) |

Configurer : `MAMI_DRIVER_OFFLINE_THRESHOLD_SECONDS` (défaut 300s).

Commande planifiée : `php artisan drivers:mark-offline` (chaque minute).

### Audit `ride_events`

Tous les changements de cycle de vie sont persistés (`event_type`, `payload`, timestamps).

### ETA / distance

`DistanceRefreshService` recalcule distance Haversine + ETA simple (`distance / vitesse moyenne`).

## Comptes démo (seed)

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Client | `client@mami.ga` | `password` |
| Chauffeur | `jean.driver@mami.ga` | `password` |

## Endpoints principaux

**Auth** : `POST /api/register`, `/api/login`, `/api/logout`, `GET /api/me`

**Drivers** : `GET /api/drivers/nearby`, `POST /api/drivers/location/update`, `POST /api/drivers/availability`, `GET /api/drivers/{id}/live-location`

**Rides** : `POST /api/rides/request`, `/api/rides/{id}/accept`, `/arrived`, `/start`, `/complete`, `GET /api/rides/{id}`, `GET /api/rides/{id}/tracking`, `GET /api/rides/history`

Authentification : `Authorization: Bearer {token}`

## Tests

Les tests utilisent **MySQL uniquement** (même stack que la production), via une base dédiée.

```bash
# Créer la base de tests (une fois)
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS mami_ga_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

php artisan test
```

Dans `.env` :

```env
DB_TEST_DATABASE=mami_ga_testing
DB_TEST_USERNAME=votre_user
DB_TEST_PASSWORD=votre_mot_de_passe
```

Si `DB_TEST_*` est omis, les tests réutilisent `DB_HOST` / `DB_USERNAME` / `DB_PASSWORD` de `.env` avec la base `mami_ga_testing`.

## Licence

MIT
