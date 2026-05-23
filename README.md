# MAMI.GA

Plateforme intelligente de mobilité urbaine pour l’Afrique — réservation taxi, GPS, dispatch et suivi temps réel.

## Technologies

- Laravel 13 (API)
- Sanctum (authentification token)
- MySQL
- Flutter, Firebase, Google Maps, Asterisk SIP, Mobile Money (phases futures)

## État du projet

| Phase | Statut | Contenu |
|-------|--------|---------|
| **1 — MVP Backend** | En cours | API REST taxi |
| 2 — Temps réel | À venir | Firebase, notifications |
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

## Comptes démo (seed)

| Rôle | Email | Mot de passe |
|------|-------|--------------|
| Client | `client@mami.ga` | `password` |
| Chauffeur | `jean.driver@mami.ga` | `password` |

## Endpoints principaux

- `POST /api/register`, `/api/login`, `/api/logout`, `GET /api/me`
- `GET /api/drivers/nearby`, `POST /api/drivers/location/update`, `POST /api/drivers/availability`
- `POST /api/rides/request`, `/api/rides/{id}/accept`, `/start`, `/complete`, `GET /api/rides/{id}`, `GET /api/rides/history`

Authentification : `Authorization: Bearer {token}`

## Tests

```bash
php artisan test
```

## Licence

MIT
