# Rapport d'avancement — P3 Dispatch intelligent

**Sprint :** P3 — **LIVRÉ (validation terrain en attente)**  
**Branche :** `feature/mami-taxi-v2-p2`  
**Baseline :** P2B validé  
**Date :** 2026-06-12  

---

## Statut livrables

| Livrable | Statut |
|----------|--------|
| Tables `ride_offers`, `ride_dispatch_waves` | ✅ |
| `RideDispatchEngine` (vagues, scoring, expiration) | ✅ |
| `RideOfferService` (création, accept, reject) | ✅ |
| `RideDispatchScoringService` | ✅ |
| Jobs `DispatchWaveJob`, `ExpireRideSearchJob` | ✅ |
| Events `RideOfferCreated`, `RideOfferAccepted`, `RideSearchExpired` | ✅ |
| Logs structurés `[DISPATCH]`, `[WAVE]`, etc. | ✅ |
| API offres + hook dispatch post-booking | ✅ |
| Récupération courses `searching` existantes | ✅ |
| Tests feature | ✅ |
| `P3_DISPATCH_DEBUGGING.md` | ✅ |
| Tag `v2-p3-stable` | ⏳ Après validation terrain |

---

## Architecture

```
POST /rides/request (P2A/P2B inchangé)
  → RideBookingService::createTextBooking()
  → RideDispatchEngine::start()  [si MAMI_DISPATCH_V2=true]
      → DispatchWaveJob (vague 0)
          → filtrage chauffeurs [DRIVER_FILTER]
          → scoring [SCORING]
          → RideOfferService::createOffer() [OFFER]
          → RideOfferCreated (Reverb)
          → DispatchWaveJob (vague N+1, délai 15s)
  → ExpireRideSearchJob (scheduler /min)
      → status=expired après 2h [EXPIRE]
```

### Vagues progressives

| Vague | Rayon |
|-------|-------|
| 0 | 0–1 km |
| 1 | 1–3 km |
| 2 | 3–5 km |
| 3 | 5–10 km |
| 4 | 10–20 km |

Config : `config/mami.php` — délai vague, max chauffeurs/vague, poids scoring.

### Éligibilité chauffeur

- `status = online`
- `is_available = true`
- Coordonnées valides
- Non déjà dans `ride_offers` pour cette course

### Compatibilité P2A/P2B

- Contrat `POST /rides/request` **inchangé**
- Dispatch déclenché **après** création, sans modifier le payload client
- Courses `searching` sans `dispatch_started_at` → reprise via `recoverPendingSearches()`

---

## API

| Méthode | Route | Rôle |
|---------|-------|------|
| `POST` | `/api/rides/request` | Inchangé — déclenche dispatch si V2 |
| `GET` | `/api/rides/offers/current` | Offres pending chauffeur |
| `POST` | `/api/rides/{ride}/offers/{offer}/accept` | Acceptation first-wins |
| `POST` | `/api/rides/{ride}/offers/{offer}/reject` | Refus |
| `GET` | `/api/rides/current?as_client=1` | Course searching/active client |

---

## Fichiers créés

```
app/Enums/RideOfferStatus.php
app/Models/RideOffer.php
app/Models/RideDispatchWave.php
app/Services/RideDispatchEngine.php
app/Services/RideOfferService.php
app/Services/RideDispatchScoringService.php
app/Services/AddressHintService.php
app/Support/Dispatch/DispatchLogger.php
app/Support/Geo/GeoPoint.php
app/Jobs/DispatchWaveJob.php
app/Jobs/ExpireRideSearchJob.php
app/Events/RideOfferCreated.php
app/Events/RideOfferAccepted.php
app/Events/RideSearchExpired.php
app/Http/Controllers/Api/RideOfferController.php
app/Http/Resources/RideOfferResource.php
database/migrations/2026_06_12_300000_create_ride_offers_table.php
database/migrations/2026_06_12_300001_create_ride_dispatch_waves_table.php
tests/Feature/RideDispatchV2Test.php
tests/Feature/RideOfferTest.php
tests/Feature/RideExpirationTest.php
docs/P3_DISPATCH_DEBUGGING.md
docs/P3_DISPATCH_PROGRESS_REPORT.md
```

---

## Tests

```bash
php artisan test --filter=RideDispatch
php artisan test --filter=RideOffer
php artisan test --filter=RideExpiration
```

| Fichier | Scénarios |
|---------|-----------|
| `RideDispatchV2Test` | Offre chauffeur proche, offline exclu, reprise searching, V1 bloqué |
| `RideOfferTest` | Accept first-wins, reject, liste offres |
| `RideExpirationTest` | Expiration 2h + offres pending |

---

## Déploiement VPS

```bash
git pull origin feature/mami-taxi-v2-p2
php artisan migrate --force
# .env
MAMI_DISPATCH_V2=true
php artisan config:clear
php artisan queue:restart
# Cron : schedule:run + queue:work
```

---

## Hors périmètre (respecté)

- ❌ GPS post-acceptation (P4)
- ❌ Paiement réel
- ❌ Négociation / counter-offer (P5)
- ❌ Réservation programmée
- ❌ UI Flutter chauffeur/client (backend P3 uniquement)

---

## Documents associés

- [P3_DISPATCH_DEBUGGING.md](./P3_DISPATCH_DEBUGGING.md)
- [P2B_PROGRESS_REPORT.md](./P2B_PROGRESS_REPORT.md)
- [P2_IMPLEMENTATION_PLAN.md](./P2_IMPLEMENTATION_PLAN.md)
