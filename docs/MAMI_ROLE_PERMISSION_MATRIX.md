# MAMI — Matrice Rôles & Permissions

**Version** : 1.0  
**Date** : juin 2026  
**Seeder** : `database/seeders/RolePermissionSeeder.php`

---

## Rôles globaux

| Slug | Nom affiché | Module | Description |
|------|-------------|--------|-------------|
| `citizen` | Citoyen | core | Utilisateur de base |
| `taxi_customer` | Client Taxi | taxi | Demande des courses |
| `taxi_driver` | Chauffeur Taxi | taxi | Accepte et exécute des courses |
| `carpool_driver` | Conducteur Covoiturage | carpool | Publie des trajets partagés |
| `carpool_passenger` | Passager Covoiturage | carpool | Réserve des places |
| `transport_customer` | Client Transport | transport | Demande transport marchandises |
| `transport_driver` | Transporteur | transport | Exécute missions TM |
| `merchant` | Commerçant | commerce | Gère fiche PME |
| `municipal_agent` | Agent Municipal | municipality | Accès outils mairie |
| `admin` | Administrateur | core | Backoffice |
| `super_admin` | Super Administrateur | core | Tous droits |

> Un utilisateur peut cumuler plusieurs rôles (table `user_roles`).

---

## Permissions

| Slug | Module | Description |
|------|--------|-------------|
| `taxi.rides.request` | taxi | Créer une demande de course |
| `taxi.rides.dispatch` | taxi | Recevoir / accepter offres |
| `taxi.rides.manage` | taxi | Administration courses |
| `carpool.trips.publish` | carpool | Publier un trajet |
| `carpool.trips.book` | carpool | Réserver une place |
| `transport.requests.create` | transport | Créer une demande fret |
| `transport.missions.manage` | transport | Gérer missions |
| `commerce.merchants.manage` | commerce | CRUD commerce |
| `commerce.merchants.view` | commerce | Consulter annuaire |
| `municipality.dashboard.view` | municipality | Tableau de bord mairie |
| `municipality.collections.manage` | municipality | Recouvrements |
| `core.admin.access` | core | Accès admin |
| `core.super_admin.access` | core | Super admin |

---

## Matrice Rôle → Permissions

| Rôle | Permissions |
|------|-------------|
| **citizen** | `commerce.merchants.view` |
| **taxi_customer** | `taxi.rides.request`, `commerce.merchants.view` |
| **taxi_driver** | `taxi.rides.dispatch` |
| **carpool_driver** | `carpool.trips.publish` |
| **carpool_passenger** | `carpool.trips.book`, `commerce.merchants.view` |
| **transport_customer** | `transport.requests.create`, `commerce.merchants.view` |
| **transport_driver** | `transport.missions.manage` |
| **merchant** | `commerce.merchants.manage` |
| **municipal_agent** | `municipality.dashboard.view`, `municipality.collections.manage` |
| **admin** | `core.admin.access`, `taxi.rides.manage` |
| **super_admin** | **Toutes** |

---

## Migration depuis l'existant

| Ancien mécanisme | Nouveau |
|------------------|---------|
| `users.is_admin = true` | Rôle `admin` (auto via seeder) |
| Existence `drivers` | Rôle `taxi_driver` (auto via seeder) |
| Middleware `admin` | Conservé — `isAdmin()` vérifie aussi les rôles |

### API utilisateur

```php
$user->hasRole('taxi_driver');
$user->hasPermission('taxi.rides.dispatch');
```

---

## Contrôle d'accès modules API

| Couche | Mécanisme |
|--------|-----------|
| Module désactivé | Middleware `module:carpool` → 403 |
| Permission fine | À brancher progressivement sur controllers |
| Admin web | Middleware `admin` existant |

---

## Évolution prévue

| Phase | Ajout |
|-------|-------|
| 2 | `payments.capture`, `payments.refund` |
| 3 | `carpool.trips.cancel`, `carpool.trips.negotiate` |
| 4 | `transport.quotes.submit` |
| 5 | `commerce.reviews.moderate` |
| 6 | `municipality.reports.assign` |

---

## Tests

`tests/Feature/SuperAppArchitectureTest.php` — rôles seedés, assignation, modules.
