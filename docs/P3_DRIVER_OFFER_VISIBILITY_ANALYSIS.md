# Analyse visibilité offres — mami_driver P3-FE

**Date :** 2026-06-12  
**Contexte terrain :** rides 15/16, offres `pending` en base pour drivers 1 et 3, **aucune offre visible** sur téléphone chauffeur.

---

## 1. GET `/api/rides/offers/current`

### URL exacte

```
GET https://api.mami.ga/api/rides/offers/current
```

(Préfixe = `AppConfig.apiBaseUrl` dans l'app chauffeur, défaut `https://api.mami.ga/api`)

### Authentification

```
Authorization: Bearer {sanctum_token}
Accept: application/json
```

Le token est injecté par l'intercepteur Dio (`api_client.dart`). L'utilisateur doit être un **chauffeur** : `$request->user()->driver` non null, sinon **403**.

### Réponse JSON attendue (succès)

```json
{
  "success": true,
  "message": "Pending ride offers retrieved",
  "data": [
    {
      "id": 1,
      "ride_id": 15,
      "driver_id": 3,
      "status": "pending",
      "offered_price": 5000,
      "distance_to_pickup_km": 0.12,
      "radius_wave": "0-1km",
      "expires_at": "2026-06-12T22:30:00+00:00",
      "ride": {
        "id": 15,
        "status": "searching",
        "pickup_label": "Carrefour STFO",
        "destination_label": "Sni owendo",
        "proposed_price": 5000,
        "payment_method": "cash",
        "pickup_latitude": null,
        "pickup_longitude": null,
        "client": { "id": 2, "name": "...", "phone": "..." }
      }
    }
  ]
}
```

Liste vide `data: []` si aucune offre **éligible** (voir §6).

### Vérification curl

```bash
curl -s https://api.mami.ga/api/rides/offers/current \
  -H "Authorization: Bearer TOKEN_DRIVER_3" \
  -H "Accept: application/json" | jq .
```

**Important :** le chauffeur connecté sur le téléphone doit correspondre au `driver_id` des offres (1 ou 3).

### Logs backend (après correctif)

```
[OFFERS_API] driver #3 pending_count=1
[OFFERS_API] driver #3 has 2 time-expired pending offer(s) hidden from API
```

---

## 2. `pendingOffersProvider` — diagnostic

| Paramètre | Avant correctif | Après correctif |
|-----------|-----------------|-----------------|
| Poll interval | 8 s (`ridePollInterval`) | **5 s** (`offerPollInterval`) |
| Premier fetch | Constructeur (auth pas prête) | Après `authStateProvider` + HomeScreen |
| Erreurs | `AsyncValue.error` parfois masqué | Carte erreur **en premier** avec message |
| Reverb | Subscribe si `driverId` null → **jamais** | Retry via `authStateProvider.listen` + chaque `refresh()` |
| Logs | Aucun | `[OFFERS_FETCH_*]`, `[REVERB_*]` |

### Erreurs silencieuses identifiées (corrigées)

1. **Fetch avant auth** — `refresh()` au constructeur sans `driver.id` → échec ou liste vide.
2. **Parse JSON strict** — `id as int` échouait si API renvoie num → **toute la liste rejetée**.
3. **Reverb jamais abonné** — `startHybridTracking()` appelé avant chargement session → `driverId == null` → pas de subscription permanente.
4. **UI** — état erreur affiché après « en attente », peu visible.

---

## 3. `HomeScreen` — branchement

### Provider

```dart
ref.watch(pendingOffersProvider);
ref.read(pendingOffersProvider.notifier).startHybridTracking();
```

Appelé dans `initState` **et** via `ref.listen(authStateProvider)` dans le provider.

### Affichage `RideOfferCard`

Ordre de priorité (après correctif) :

1. Erreur offres → carte rouge + détail + Réessayer
2. Course active (`accepted`) → tuile course en cours
3. **`offers.isNotEmpty`** → `RideOfferCard` pour chaque offre
4. V1 `ride.isPending` → `IncomingRideCard`
5. Loading offres
6. En attente

---

## 4. Reverb

| Élément | Valeur |
|---------|--------|
| Canal | `private-driver-{driverId}` |
| Event | `RideOfferCreated` |
| Handler | `dispatchEvents` → `refresh()` + `[REVERB_OFFER_RECEIVED]` |

### Subscription effective ?

Avant : **souvent non** (auth non chargée au premier `startHybridTracking`).

Après : `ref.listen(authStateProvider)` + `_ensureDriverRealtime()` à chaque fetch réussi.

### Test sans Reverb

Le poll **5 s** suffit pour affichage < 10 s si l'API retourne des offres.

---

## 5. Logs Flutter ajoutés

| Tag | Moment |
|-----|--------|
| `[OFFERS_FETCH_START]` | Début fetch (driverId + URL) |
| `[OFFERS_FETCH_SUCCESS]` | Réponse OK |
| `[OFFERS_FETCH_COUNT]` | Nombre d'offres parsées |
| `[OFFERS_FETCH_ERROR]` | Erreur réseau / auth / parse |
| `[OFFERS_PARSE_ERROR]` | Échec parse d'une offre |
| `[REVERB_SUBSCRIBE]` | Abonnement canal chauffeur |
| `[REVERB_OFFER_RECEIVED]` | Event `RideOfferCreated` |

Fichier : `mobile/mami_driver/lib/core/logging/offers_logger.dart`

Filtrer logcat :

```bash
adb logcat | grep -E "OFFERS_|REVERB_"
```

---

## 6. Offres expirées masquées (cause racine probable)

### Comportement API

`RideOfferService::pendingOffersForDriver()` filtre :

```php
->where('status', 'pending')
->where('expires_at', '>', now())
->whereHas('ride', status = searching)
```

### Problème terrain

- Timeout offre par défaut était **30 secondes** (`MAMI_OFFER_TIMEOUT_SECONDS`).
- En base : `status = pending` mais `expires_at` **dépassé** → API retourne `[]`.
- Le chauffeur voit « En attente d'offres » alors que MySQL montre encore `pending`.

### Correctifs appliqués

| Correctif | Détail |
|-----------|--------|
| Timeout défaut | **30 s → 120 s** (`config/mami.php`) |
| `ExpireStaleOffersJob` | Marque `expired` les offres dont `expires_at` est passé (scheduler /min) |
| Log `[OFFERS_API]` | Signale offres time-expired masquées |

### Vérification MySQL

```sql
SELECT id, ride_id, driver_id, status, expires_at, NOW() as now_utc
FROM ride_offers
WHERE ride_id IN (15, 16);
```

Si `expires_at < NOW()` → offre **masquée** par l'API (normal).

---

## 7. Checklist validation terrain

1. Installer **nouvel APK chauffeur** (post-correctif).
2. Connexion compte chauffeur **driver #1 ou #3** (vérifier `/me` → `driver.id`).
3. Passer **En ligne** + GPS actif.
4. Client crée une course → attendre **≤ 5 s**.
5. Logcat : `[OFFERS_FETCH_COUNT] 1` ou plus.
6. `RideOfferCard` visible.
7. curl `/rides/offers/current` avec le même token → `data` non vide.

### Scénarios

| Scénario | Attendu |
|----------|---------|
| Chauffeur < 5 m | Offre vague 0-1 km, visible < 10 s |
| Chauffeur 100 m | Idem |
| Chauffeur 1 km | Offre si dans vague |
| Chauffeur offline | Pas d'offre nouvelle ; offres existantes masquées si expirées |
| Aucun chauffeur | Client searching jusqu'à expiration 2 h |

---

## 8. Correctifs livrés (résumé)

### Flutter chauffeur

- `OffersLogger` + logs détaillés
- Poll offres **5 s**
- Auth-ready avant fetch + listen auth
- Reverb subscribe après auth
- Parse JSON tolérant (`num` → `int`)
- HomeScreen : erreur prioritaire, loading offres

### Backend

- Timeout offre **120 s**
- `ExpireStaleOffersJob`
- Logs `[OFFERS_API]`

---

## 10. Incident production 2026-06-13 — rides 22–24 sans offres

### Symptômes logs

```
[DISPATCH] Ride #24 searching
[OFFERS_API] driver #1 pending_count=0
[OFFERS_API] driver #1 has 3 time-expired pending offer(s) hidden from API
```

**Absent :** `[WAVE]`, `[OFFER]`, `[SCORING]`

### Cause racine

`DispatchWaveJob` était **mis en queue** (`QUEUE_CONNECTION=database`) sans `queue:work` actif sur le VPS.  
Résultat : `dispatch_started_at` renseigné, **aucune vague exécutée**, **aucune `ride_offer` créée** pour rides 22–24.

Les 3 offres « pending » visibles en MySQL datent du **2026-06-12** (`expires_at` dépassé) — bruit dans les logs, pas les offres courantes.

### Correctif code (commit suivant)

- Vague **0 exécutée synchrone** dans `RideDispatchEngine::start()`
- `recoverStuckDispatches()` pour rides `searching` sans `ride_dispatch_waves`
- Expiration stale inline à chaque `GET /offers/current`

### Actions VPS immédiates (avant ou après deploy)

```bash
# 1. Débloquer rides 22–24 manuellement
php artisan tinker
>>> app(\App\Services\RideDispatchEngine::class)->recoverStuckDispatches();

# 2. Worker queue (vagues 1–3+)
php artisan queue:work --daemon &

# 3. Scheduler (expiration + recovery)
# crontab: * * * * * cd /var/www/mami.ga && php artisan schedule:run

# 4. Nettoyer offres fantômes
mysql -e "UPDATE ride_offers SET status='expired', responded_at=NOW() WHERE status='pending' AND expires_at < NOW();"
```

### Après correctif — logs attendus

```
[DISPATCH] Ride #25 searching
[WAVE] Ride #25 wave 0-1km started
[SCORING] Driver #1 score=...
[OFFER] Ride #25 offered to driver #1
[WAVE] Ride #25 wave 0-1km ended drivers_notified=1
[OFFERS_API] driver #1 pending_count=1
```


1. `curl /rides/offers/current` avec token du téléphone
2. Comparer `driver.id` du `/me` avec `ride_offers.driver_id`
3. Vérifier `expires_at > now()` et `rides.status = searching`
4. Logcat `[OFFERS_FETCH_ERROR]` sur le téléphone
5. Logs VPS `[OFFERS_API]` et `[OFFER]`
