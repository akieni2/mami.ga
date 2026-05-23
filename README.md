# MAMI.GA

<<<<<<< HEAD
MAMI.GA est une plateforme intelligente de réservation de taxis avec géolocalisation temps réel, suivi GPS et paiements mobile money.

## Technologies

- Laravel
- Flutter
- Firebase
- Google Maps
- Asterisk SIP

## Fonctionnalités prévues

- Réservation taxi
- Tracking GPS
- Temps réel
- Appels chauffeur/client
- Mobile Money
- Dashboard administration

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
=======
Plateforme intelligente de mobilité urbaine pour l’Afrique — réservation taxi, GPS, dispatch, suivi temps réel.

## État du projet

| Phase | Statut | Contenu |
|-------|--------|---------|
| **1 — MVP Backend** | En cours | API Laravel (ce dépôt) |
| 2 — Temps réel | À venir | Firebase, notifications |
| 3 — Mobile | À venir | Flutter client + chauffeur |
| 4 — VoIP | À venir | Asterisk SIP |
| 5 — Paiements | À venir | Mobile Money |

## Démarrage rapide

Le backend Phase 1 se trouve dans [`backend/`](backend/README.md).

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

> **Note :** PHP et Composer doivent être installés sur la machine (ex. Laragon sous Windows).

## Documentation

- [Backend API — Phase 1](backend/README.md)
- Document fondateur : spécifications MVP dans la conversation projet / instructions Phase 1

## Stack Phase 1

- Laravel 12 + Sanctum
- MySQL
- API REST JSON
- Dispatch GPS simple (Haversine)

## Licence

MIT
>>>>>>> ea509b190013e593185153179b65d57eb1684190
