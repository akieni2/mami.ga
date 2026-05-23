# MAMI.GA — Backend API (Phase 1 MVP)

API Laravel 12 pour la plateforme de mobilité urbaine **MAMI.GA** : authentification Sanctum, chauffeurs, véhicules, courses et GPS.

## Prérequis

- PHP 8.2+
- Composer 2.x
- MySQL 8+ (ou SQLite pour les tests)

Recommandé sous Windows : [Laragon](https://laragon.org/) ou Docker (voir ci-dessous).

## Installation

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```

Configurer MySQL dans `.env`, puis :

```bash
php artisan migrate --seed
php artisan serve
```

L’API est disponible sur `http://127.0.0.1:8000/api`.

## Comptes de démo (seed)

| Rôle   | Email                 | Mot de passe |
|--------|-----------------------|--------------|
| Client | `client@mami.ga`      | `password`   |
| Chauffeur | `jean.driver@mami.ga` | `password` |
| Chauffeur | `paul.driver@mami.ga` | `password` |

## Tests

```bash
php artisan test
```

Couverture MVP : inscription, connexion, demande de course, acceptation, démarrage, fin de course.

## Endpoints principaux

| Méthode | Route | Description |
|---------|-------|-------------|
| POST | `/api/register` | Inscription client |
| POST | `/api/login` | Connexion + token |
| POST | `/api/logout` | Déconnexion (auth) |
| GET | `/api/me` | Utilisateur courant |
| GET | `/api/drivers/nearby` | Chauffeurs proches |
| POST | `/api/drivers/location/update` | Mise à jour GPS |
| POST | `/api/drivers/availability` | Disponibilité |
| POST | `/api/rides/request` | Demander un taxi |
| POST | `/api/rides/{id}/accept` | Accepter |
| POST | `/api/rides/{id}/start` | Démarrer |
| POST | `/api/rides/{id}/complete` | Terminer |
| GET | `/api/rides/{id}` | Détail course |
| GET | `/api/rides/history` | Historique |

Authentification : en-tête `Authorization: Bearer {token}`.

Format de réponse :

```json
{
  "success": true,
  "message": "...",
  "data": {}
}
```

## Logique dispatch MVP

1. Le client envoie pickup + destination.
2. Le service cherche les chauffeurs `is_available` + `online` dans un rayon configurable (`MAMI_DRIVER_SEARCH_RADIUS_KM`).
3. Le chauffeur le plus proche (Haversine) est assigné.
4. La course est créée en statut `pending`.

## Docker (optionnel)

```bash
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --seed
```

## Structure

```
app/
  Enums/          # RideStatus, DriverStatus
  Http/
    Controllers/Api/
    Requests/
    Resources/
  Models/
  Services/       # RideDispatchService, DriverLocationService
  Support/        # ApiResponse, GeoDistance
database/
  migrations/
  factories/
  seeders/
routes/api.php
tests/Feature/
```

## Prochaines phases (hors scope)

- Flutter, Firebase, Google Maps, SIP, Mobile Money
