# Rapport P0 — Audit technique MAMI Taxi V2

**Branche :** `feature/mami-taxi-v2-p1`  
**Référence :** [MAMI_TAXI_V2.md](./MAMI_TAXI_V2.md) (commit `314a923`)  
**Date :** 2026-05-25

---

## 1. Objectif P0

Poser les fondations avant P1 : audit, feature flags, schéma `rides` préparé, modèles enums, sans activer dispatch V2 ni paiements.

---

## 2. Composants réutilisables

### Backend Laravel

| Composant | Fichier | Réutilisation V2 |
|-----------|---------|------------------|
| Distance haversine | `app/Support/GeoDistance.php` | Estimation, dispatch, tracking |
| Prix trajet | `RideDispatchService::estimatePrice()` | Extrait → `RideEstimateService` (P1) |
| GPS chauffeur | `DriverLocationService` | Dispatch P3, verrous P7 |
| Tracking | `RideTrackingService` | Course active inchangée |
| Reverb | `app/Events/*`, `routes/channels.php` | Offres P4 |
| Audit | `RideEventRecorder` | Étendre types P3+ |

### Client `mami_client`

| Composant | Fichier | Réutilisation V2 |
|-----------|---------|------------------|
| Carte OSM | `lib/core/map/mami_map.dart` | P1 booking V2 |
| Route / ETA local | `lib/core/map/route_utils.dart` | Fallback + affichage |
| Prix local | `lib/core/utils/price_utils.dart` | Fallback hors-ligne |
| GPS | `user_location_provider.dart` | Pickup auto P1 |
| Reverb | `reverb_service.dart` | P4 |
| Auth | `auth_repository.dart` | Inchangé |

### Chauffeur `mami_driver`

| Composant | Fichier | Réutilisation V2 |
|-----------|---------|------------------|
| GPS upload 10s | `location_tracker_provider.dart` | Dispatch P3 |
| Offre entrante | `incoming_ride_card.dart` | Enrichir P4 |
| Reverb | `reverb_service.dart` | Payload offres P4 |

**Aucune modification chauffeur en P0/P1** (conforme au périmètre).

---

## 3. Dette technique identifiée

| Priorité | Sujet | État P0/P1 |
|----------|-------|------------|
| Haute | Splash bypass diagnostic (`GO LOGIN` forcé) | **Corrigé** — `bootstrap()` restauré |
| Haute | `authStateProvider` initial `data(null)` | OK (commit `ed83e1a`) |
| Haute | Router recréé à chaque changement auth | Documenté — refactor P9 |
| Moyenne | iOS `Info.plist` sans permission GPS | **Corrigé** P1 |
| Moyenne | `ride_map.dart` legacy non utilisé | À supprimer P9 |
| Moyenne | Logs `debugPrint` router/auth | À retirer P9 |
| Basse | Distance ligne droite (pas OSRM) | Phase ultérieure |
| Basse | `GET /rides/current` client absent | Prévu P3 |

---

## 4. Impacts des futures phases

| Phase | Dépendances P0 | Fichiers clés à étendre |
|-------|----------------|-------------------------|
| P2 Prix proposé | Colonnes `proposed_price`, `suggested_price` | `RequestRideRequest`, booking UI |
| P3 Dispatch | `MAMI_DISPATCH_V2`, tables `ride_offers` | `RideDispatchEngine`, jobs |
| P4 Reverb offres | Channels existants | Events `RideOfferSent` |
| P5 Négociation | Statut `negotiating` | `RideNegotiationService` |
| P6 Paiement | `payment_method` colonne | `PaymentService` |
| P7 Programmé | `scheduled_at`, `booking_type` | `ScheduledRideService`, locks |
| P7b No-show | `cancelled_*`, `no_show_*` colonnes | `RideCancellationService` |
| P8 Avis | — | `driver_reviews` |

---

## 5. Livrables P0 réalisés

### Feature flags

| Variable | Fichier | Défaut |
|----------|---------|--------|
| `MAMI_TAXI_V2` | `config/mami.php`, `.env.example` | `false` |
| `MAMI_DISPATCH_V2` | idem | `false` |
| Helper | `app/Support/MamiFeatures.php` | — |
| API publique | `GET /api/app/features` | — |

### Migrations préparées

- `database/migrations/2026_05_25_100000_add_v2_prepared_fields_to_rides_table.php` — colonnes nullable `rides`
- `database/migrations/v2/README.md` — plan tables futures

### Modèles / enums

- `app/Enums/BookingType.php`
- `app/Enums/PaymentMethod.php`
- `app/Models/Ride.php` — fillable + casts V2

### Client

- `lib/core/config/app_features.dart`
- `lib/core/config/app_features_provider.dart`

---

## 6. Fichiers modifiés (P0)

```
.env.example
config/mami.php
app/Support/MamiFeatures.php
app/Enums/BookingType.php
app/Enums/PaymentMethod.php
app/Models/Ride.php
app/Http/Resources/RideResource.php
database/migrations/2026_05_25_100000_add_v2_prepared_fields_to_rides_table.php
database/migrations/v2/README.md
mobile/mami_client/lib/core/config/app_features.dart
mobile/mami_client/lib/core/config/app_features_provider.dart
docs/P0_AUDIT_REPORT.md
```

---

## 7. Prochaine étape

Phase **P1** — écran réservation GPS (`RideBookingV2Screen`) + `POST /api/rides/estimate`.
