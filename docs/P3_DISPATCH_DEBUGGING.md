# P3 — Guide de diagnostic dispatch

**Sprint :** Dispatch intelligent chauffeur (V2)  
**Branche :** `feature/mami-taxi-v2-p2`  
**Prérequis VPS :** `MAMI_DISPATCH_V2=true`, queue worker actif, scheduler actif

---

## 1. Suivre un dispatch complet

### Flux attendu

```
Client POST /rides/request (text-first)
  → status=searching, driver_id=null
  → [DISPATCH] Ride #N searching
  → [WAVE] Ride #N wave 0-1km started
  → [SCORING] Driver #X score=...
  → [OFFER] Ride #N offered to driver #X
  → [WAVE] Ride #N wave 0-1km ended drivers_notified=K
  → (15s) vague 1-3km, puis 3-5, 5-10, 10-20
  → Chauffeur accepte
  → [ACCEPT] Ride #N accepted by driver #X
  → status=accepted, agreed_price=proposed_price
```

### Logs Laravel (filtrage)

```bash
# Dispatch global
grep '\[DISPATCH\]' storage/logs/laravel.log | tail -50

# Vagues
grep '\[WAVE\]' storage/logs/laravel.log | tail -50

# Offres
grep '\[OFFER\]' storage/logs/laravel.log | tail -50

# Filtres chauffeurs (pourquoi exclus)
grep '\[DRIVER_FILTER\]' storage/logs/laravel.log | tail -50

# Scoring
grep '\[SCORING\]' storage/logs/laravel.log | tail -50

# Acceptation
grep '\[ACCEPT\]' storage/logs/laravel.log | tail -50

# Expiration 2h
grep '\[EXPIRE\]' storage/logs/laravel.log | tail -50
```

### Tables MySQL à consulter

```sql
-- Course en recherche
SELECT id, status, driver_id, pickup_label, destination_label,
       pickup_latitude, pickup_longitude,
       dispatch_started_at, dispatch_expires_at, proposed_price
FROM rides WHERE id = ?;

-- Offres envoyées
SELECT id, ride_id, driver_id, status, distance_to_pickup_km,
       dispatch_score, radius_wave, expires_at, responded_at
FROM ride_offers WHERE ride_id = ? ORDER BY id;

-- Vagues exécutées
SELECT id, radius_min_km, radius_max_km, drivers_notified, started_at, ended_at
FROM ride_dispatch_waves WHERE ride_id = ? ORDER BY started_at;

-- Chauffeur candidat
SELECT id, status, is_available, latitude, longitude, rating, last_seen_at
FROM drivers WHERE id = ?;
```

---

## 2. Diagnostiquer « aucun chauffeur trouvé »

| Cause | Log attendu | Vérification |
|-------|-------------|--------------|
| Dispatch V2 désactivé | Pas de `[DISPATCH]` | `MAMI_DISPATCH_V2=true` dans `.env`, `php artisan config:clear` |
| Queue inactive | `[DISPATCH]` mais pas de `[WAVE]` | `php artisan queue:work` ou `QUEUE_CONNECTION=sync` |
| Aucun chauffeur online | `[DRIVER_FILTER] ... offline` | `drivers.status = online` |
| Chauffeur indisponible | `[DRIVER_FILTER] ... unavailable` | `drivers.is_available = true` |
| Pas de GPS | `[DRIVER_FILTER] ... no coordinates` | `latitude`/`longitude` non null |
| Hors rayon vague | Pas de `[OFFER]` | Distance vs vague courante (0-1, 1-3 km…) |
| Déjà sollicité | `[DRIVER_FILTER] ... already solicited` | `ride_offers` UNIQUE(ride_id, driver_id) |
| Point recherche approximatif | `[DISPATCH] ... fallback Libreville center` | Course text-only sans coords pickup |
| Recherche expirée | `[EXPIRE] Ride #N expired` | `dispatch_expires_at` dépassé (2h) |

### Commandes utiles

```bash
# Relancer dispatch pour courses searching sans dispatch_started_at
php artisan tinker
>>> app(\App\Services\RideDispatchEngine::class)->recoverPendingSearches();

# Forcer expiration (scheduler)
php artisan schedule:run

# Tester une vague manuellement
>>> app(\App\Services\RideDispatchEngine::class)->processWave(RIDE_ID, 0);
```

---

## 3. Diagnostiquer « chauffeur proche non sollicité »

### Checklist terrain

1. **Distance réelle** — comparer coords chauffeur vs point de recherche (pickup coords ou centre Libreville).
2. **Vague en cours** — un chauffeur à 100 m est dans la vague `0-1km` ; à 1,5 km il attend la vague `1-3km` (+15 s de délai).
3. **Limite par vague** — max `MAMI_DISPATCH_WAVE_MAX_DRIVERS` (défaut 5) ; scoring peut exclure un chauffeur moins bien noté.
4. **Statut présence** — `drivers:mark-offline` peut passer offline si `last_seen_at` > 5 min.
5. **Course active** — chauffeur `on_ride` ou `is_available=false` → filtré.
6. **Offre précédente** — refus ou offre existante → `already solicited`.

### Scénarios terrain documentés

| Distance | Vague | Délai approximatif |
|----------|-------|-------------------|
| < 5 m | 0-1 km | Immédiat (wave 0) |
| 100 m | 0-1 km | Immédiat |
| 1 km | 0-1 km | Immédiat |
| 2 km | 1-3 km | ~15 s après wave 0 |
| 5 km | 3-5 km | ~30-45 s |
| Offline | — | `[DRIVER_FILTER] rejected: offline` |

### Vérifier le scoring

```bash
grep "\[SCORING\] Driver #ID" storage/logs/laravel.log
```

Poids configurables (`.env`) :

```
MAMI_DISPATCH_SCORE_DISTANCE=0.5
MAMI_DISPATCH_SCORE_AVAILABILITY=0.3
MAMI_DISPATCH_SCORE_RATING=0.2
```

---

## 4. Reverb / temps réel

### Événements P3

| Event | Canal | Déclencheur |
|-------|-------|-------------|
| `RideOfferCreated` | `private-driver-{id}` | Offre créée |
| `RideOfferAccepted` | `private-ride-{id}`, `private-user-{clientId}` | Acceptation |
| `RideSearchExpired` | `private-user-{clientId}`, `private-ride-{id}` | Expiration 2h |

### Vérifier Reverb

```bash
# Auth canal chauffeur
curl -X POST http://VPS/broadcasting/auth \
  -H "Authorization: Bearer DRIVER_TOKEN" \
  -d "channel_name=private-driver-DRIVER_ID"

# Logs Reverb (si docker)
docker logs reverb -f
```

### Test sans mobile

```bash
php artisan test --filter=RideDispatch
php artisan test --filter=RideOffer
php artisan test --filter=RideExpiration
```

---

## 5. Procédure de test terrain complète

### Prérequis

- VPS : `MAMI_TAXI_V2=true`, `MAMI_DISPATCH_V2=true`
- Migrations : `ride_offers`, `ride_dispatch_waves`
- `php artisan migrate --force`
- Scheduler : `* * * * * php artisan schedule:run`
- Queue : `php artisan queue:work` (si `QUEUE_CONNECTION=database`)
- 2 téléphones : client + chauffeur

### A. Client crée une course text-first

1. Client : Départ `Carrefour STFO`, Destination `Sni owendo`, 5000 FCFA, Espèces.
2. Vérifier écran recherche (`status=searching`).
3. MySQL : `SELECT status, dispatch_started_at FROM rides ORDER BY id DESC LIMIT 1;`
4. Logs : `grep '\[DISPATCH\]' storage/logs/laravel.log | tail -5`

### B. Chauffeur à < 5 m

1. Chauffeur : online + available + GPS actif (coords ≈ client pickup ou Libreville).
2. Attendre offre (push Reverb ou poll `GET /api/rides/offers/current`).
3. MySQL : `SELECT * FROM ride_offers WHERE ride_id=? AND status='pending';`
4. Logs : `[OFFER] Ride #N offered to driver #X`

### C. Chauffeur à 100 m

1. Placer chauffeur à ~100 m du point recherche.
2. Vague `0-1km` doit l'inclure.
3. Vérifier `distance_to_pickup_km` ≈ 0.1 dans `ride_offers`.

### D. Chauffeur à 1 km

1. Placer à ~1 km — toujours vague `0-1km` (borne max 1 km inclusive).
2. Si non sollicité : vérifier scoring et `drivers_notified` dans `ride_dispatch_waves`.

### E. Chauffeur offline

1. Passer chauffeur offline (`POST /api/drivers/availability` ou attendre cron).
2. Créer nouvelle course.
3. Log : `[DRIVER_FILTER] Driver #X rejected: offline`
4. MySQL : aucune offre pour ce chauffeur.

### F. Acceptation

1. Chauffeur : `POST /api/rides/{id}/offers/{offer}/accept`
2. Client : `GET /api/rides/current?as_client=1` → `status=accepted`
3. MySQL : `agreed_price = proposed_price`, `driver_id` renseigné
4. Log : `[ACCEPT] Ride #N accepted by driver #X`
5. Reverb : event `RideOfferAccepted`

### G. Expiration (test accéléré)

1. En staging : `dispatch_expires_at` dans le passé ou `MAMI_SEARCH_MAX_DURATION_HOURS=0` (test only).
2. `php artisan schedule:run`
3. MySQL : `status=expired`
4. Log : `[EXPIRE] Ride #N expired after search timeout`

---

## 6. Configuration VPS recommandée

```env
MAMI_TAXI_V2=true
MAMI_DISPATCH_V2=true
MAMI_DISPATCH_WAVE_DELAY_SECONDS=15
MAMI_DISPATCH_WAVE_MAX_DRIVERS=5
MAMI_OFFER_TIMEOUT_SECONDS=30
MAMI_SEARCH_MAX_DURATION_HOURS=2
QUEUE_CONNECTION=database
```

```bash
php artisan migrate --force
php artisan config:clear
php artisan queue:restart
```

---

## 7. Références

- [P3_DISPATCH_PROGRESS_REPORT.md](./P3_DISPATCH_PROGRESS_REPORT.md)
- [P2_IMPLEMENTATION_PLAN.md](./P2_IMPLEMENTATION_PLAN.md) § P3
- [DISPATCH_REAL_WORLD_TEST.md](./DISPATCH_REAL_WORLD_TEST.md)
