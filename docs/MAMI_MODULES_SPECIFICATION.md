# MAMI — Spécification des Modules

**Version** : 1.0  
**Date** : juin 2026

---

## Vue d'ensemble

| Module | Slug | Flag `.env` | API prefix | Statut |
|--------|------|-------------|------------|--------|
| Taxi | `taxi` | toujours actif | `/api/rides`, `/api/drivers` | **Production** |
| Covoiturage | `carpool` | `MAMI_MODULE_CARPOOL` | `/api/carpool` | Scaffold |
| Transport | `transport` | `MAMI_MODULE_TRANSPORT` | `/api/transport` | Scaffold |
| Commerce | `commerce` | `MAMI_MODULE_COMMERCE` | `/api/commerce` | Scaffold |
| Municipalité | `municipality` | `MAMI_MODULE_MUNICIPALITY` | `/api/municipality` | Scaffold |

---

## Module A — Taxi

### Responsabilité

Réservation, dispatch, GPS temps réel, cycle de course complet.

### Code source (legacy — ne pas déplacer sans plan de migration)

| Couche | Emplacement |
|--------|-------------|
| Controllers API | `app/Http/Controllers/Api/RideController.php`, `RideOfferController.php`, `DriverController.php` |
| Services | `app/Services/RideDispatchEngine.php`, `RideDispatchService.php`, `DriverLocationService.php`, … |
| Modèles | `app/Models/Ride.php`, `Driver.php`, `RideOffer.php`, … |
| Events Reverb | `app/Events/Ride*` |
| Module registrar | `app/Modules/Taxi/TaxiModuleServiceProvider.php` |

### Tables

`rides`, `drivers`, `vehicles`, `ride_offers`, `ride_dispatch_waves`, `ride_events`, `driver_locations`

### APIs gelées (compatibilité)

Voir `routes/api.php` — aucun changement de contrat.

---

## Module B — Covoiturage

### Objectif

Conducteur publie un trajet ; passagers réservent des places ; négociation prix.

### Structure

```
app/Modules/Carpool/
├── CarpoolModuleServiceProvider.php
├── Routes/api.php
└── Http/Controllers/CarpoolModuleController.php
```

### Endpoints actuels

| Méthode | Route | Auth | Description |
|---------|-------|------|-------------|
| GET | `/api/carpool/status` | Sanctum + module | Health / scaffold |

### Tables futures (Phase 3)

`carpool_trips`, `carpool_bookings`, `carpool_booking_offers`

### Réutilisation obligatoire

`users`, `roles`, `payments`, `ratings`, `locations`, `addresses`, `notifications`, `attachments`, `audit_logs`

---

## Module C — Transport marchandises

### Objectif

Mise en relation habitants/entreprises ↔ transporteurs (camion, pick-up, tricycle…).

### Structure

```
app/Modules/Transport/
├── TransportModuleServiceProvider.php
├── Routes/api.php
└── Http/Controllers/TransportModuleController.php
```

### Endpoints actuels

| GET | `/api/transport/status` |

### Tables futures (Phase 4)

`carriers`, `carrier_vehicles`, `freight_requests`, `freight_quotes`, `freight_missions`

### Réutilisation

Pattern dispatch inspiré de `RideDispatchEngine` ; profil `carriers` calqué sur `drivers`.

---

## Module D — Commerce & PME

### Objectif

Annuaire économique communal : recherche, filtres, géolocalisation, avis.

### Structure

```
app/Modules/Commerce/
├── CommerceModuleServiceProvider.php
├── Routes/api.php
└── Http/Controllers/CommerceModuleController.php
```

### Endpoints actuels

| GET | `/api/commerce/status` |

### Tables futures (Phase 5)

`business_categories`, `merchants`, `merchant_locations`, `merchant_hours`

### Réutilisation

`ratings`, `comments` (à créer), `addresses`, `attachments`

---

## Module E — Services municipaux

### Objectif

Portail numérique municipal : cartographie fiscale, recouvrements, signalements.

### Structure

```
app/Modules/Municipality/
├── MunicipalityModuleServiceProvider.php
├── Routes/api.php
└── Http/Controllers/MunicipalityModuleController.php
```

### Endpoints actuels

| GET | `/api/municipality/status` |

### Tables futures (Phase 6)

`municipal_offices`, `municipal_agents`, `taxpayer_accounts`, `municipal_invoices`, `citizen_reports`

### Réutilisation

`payments` (factures municipales), `audit_logs`, `attachments`

---

## Module Core (transversal)

### Emplacement

`app/Modules/Core/`

### Modèles

`Role`, `Permission`, `Address`, `Location`, `Rating`, `Attachment`, `Payment`, `Transaction`, `AuditLog`

### Enregistrement

`bootstrap/providers.php` → `CoreModuleServiceProvider`

---

## Checklist nouveau module

- [ ] Dossier `app/Modules/{Name}/`
- [ ] `{Name}ModuleServiceProvider` enregistré dans Core
- [ ] Flag `MAMI_MODULE_{NAME}` dans `config/mami.php`
- [ ] Routes sous `/api/{slug}` avec middleware `module:{slug}`
- [ ] Entrée dans `MamiFeatures::modulesConfig()`
- [ ] Tuile Flutter dans `MamiServiceModule`
- [ ] Tests Feature `Module{Name}Test`
- [ ] Mise à jour de ce document
- [ ] Aucune table dupliquée (voir `MAMI_DATABASE_MASTER_PLAN.md`)

---

## Application mobile

| Module | Écran d'entrée | Statut Flutter |
|--------|----------------|----------------|
| Taxi | `/book` | Actif |
| Autres | SnackBar « Bientôt disponible » | En attente flag API |

Fichier portail : `mobile/mami_client/lib/features/home/presentation/screens/home_screen.dart`
