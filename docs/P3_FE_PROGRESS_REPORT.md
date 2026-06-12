# Rapport d'avancement — P3-FE Frontend Dispatch

**Sprint :** P3-FE — **LIVRÉ**  
**Branche :** `feature/mami-taxi-v2-p2`  
**Prérequis backend :** `MAMI_DISPATCH_V2=true`, migrations P3, queue + scheduler  
**Date :** 2026-06-12  

---

## Objectif

Rendre le dispatch P3 utilisable sur téléphone réel :

```
Client crée course → Dispatch Laravel → Offre visible chauffeur
→ Acceptation → Client informé → Course active
```

---

## Livrables

| Livrable | Statut |
|----------|--------|
| `RideOfferModel` + repository chauffeur | ✅ |
| `pendingOffersProvider` + Reverb `RideOfferCreated` | ✅ |
| `RideOfferCard` + `HomeScreen` câblé | ✅ |
| Accept / reject offre P3 | ✅ |
| Client events `RideOfferAccepted`, `RideSearchExpired` | ✅ |
| `fetchCurrentClientRide()` + reprise splash | ✅ |
| Gestion `expired` avec message explicite | ✅ |
| APK client | ✅ |
| APK chauffeur | ✅ |

---

## mami_driver (priorité)

### Fichiers créés / modifiés

| Fichier | Rôle |
|---------|------|
| `ride_offer_model.dart` | Modèle offre P3 |
| `payment_method.dart` | Labels paiement |
| `ride_model.dart` | Coords nullable, labels texte, `proposed_price` |
| `rides_repository.dart` | `fetchCurrentOffers`, `acceptOffer`, `rejectOffer` |
| `pending_offers_provider.dart` | Poll 8 s + Reverb dispatch |
| `ride_offer_card.dart` | UI offre text-first |
| `home_screen.dart` | Affichage offres + accept/reject |
| `reverb_service.dart` | `dispatchEvents` + `RideOfferCreated` |
| `active_ride_provider.dart` | `acceptOffer()` |
| `incoming_ride_card.dart` | Coords nullable (fallback V1) |
| `active_ride_screen.dart` | Coords nullable + `displayPrice` |

### Endpoints consommés

| Méthode | Route |
|---------|-------|
| GET | `/api/rides/offers/current` |
| POST | `/api/rides/{ride}/offers/{offer}/accept` |
| POST | `/api/rides/{ride}/offers/{offer}/reject` |
| GET | `/api/rides/current` (course active post-accept) |

### Reverb

- Canal : `private-driver-{id}`
- Event : `RideOfferCreated` → refresh `pendingOffersProvider`

### Flux acceptation

1. Offre affichée sur `RideOfferCard`
2. Accepter → `POST accept` → `activeRideProvider` mis à jour
3. Navigation `/ride/active`

---

## mami_client

### Fichiers modifiés

| Fichier | Rôle |
|---------|------|
| `rides_repository.dart` | `fetchCurrentClientRide()` |
| `ride_model.dart` | `isExpired`, `isAccepted`, `isActive` corrigé |
| `reverb_service.dart` | +`RideOfferAccepted`, `RideSearchExpired` |
| `ride_searching_screen.dart` | Dialog expiration + navigation accepted |
| `splash_screen.dart` | Reprise course `searching` / `accepted` |

### Endpoints

| Méthode | Route |
|---------|-------|
| GET | `/api/rides/current?as_client=1` |
| GET | `/api/rides/{id}` | Poll existant |
| POST | `/api/rides/request` | Inchangé P2B |

### Messages utilisateur

- **Expired :** « Aucun chauffeur n'a accepté votre demande. »
- **Accepted :** navigation auto vers `/ride/active/{id}`

---

## APK

| App | Chemin | Taille |
|-----|--------|--------|
| **Client** | `mobile/mami_client/build/app/outputs/flutter-apk/app-release.apk` | 54 409 546 o (~51,87 Mo) |
| **Chauffeur** | `mobile/mami_driver/build/app/outputs/flutter-apk/app-release.apk` | 53 819 862 o (~51,31 Mo) |

**Compilation :** 2026-06-12 (client 02:33:08, chauffeur 02:34:59)

### Commandes build

```bash
# Client
cd mobile/mami_client
flutter build apk --release --dart-define=MAMI_TAXI_V2=true

# Chauffeur
cd mobile/mami_driver
flutter build apk --release
```

### Installation terrain

```bash
adb install -r mobile/mami_client/build/app/outputs/flutter-apk/app-release.apk
adb install -r mobile/mami_driver/build/app/outputs/flutter-apk/app-release.apk
```

---

## Procédure test terrain

### Prérequis VPS

```env
MAMI_TAXI_V2=true
MAMI_DISPATCH_V2=true
```

```bash
php artisan migrate --force
php artisan config:clear
php artisan queue:work &
# Cron schedule:run chaque minute
```

### Test 1 — Chauffeur < 5 m

1. Chauffeur : online + GPS actif, coords ≈ point pickup client
2. Client : commande text-first (Carrefour STFO → Sni owendo, 5000 FCFA)
3. **Attendu chauffeur :** `RideOfferCard` visible (< 15 s)
4. **Attendu logs :** `[OFFER] Ride #N offered to driver #X`
5. Accepter → navigation course active

### Test 2 — Chauffeur 100 m

1. Placer chauffeur ~100 m du point recherche
2. Client commande
3. **Attendu :** offre vague `0-1km`, distance ~0.1 km affichée

### Test 3 — Chauffeur 1 km

1. Chauffeur à ~1 km
2. **Attendu :** offre dans vague `0-1km` (borne max 1 km inclusive)

### Test 4 — Chauffeur offline

1. Chauffeur hors ligne
2. Client commande
3. **Attendu :** pas d'offre ; log `[DRIVER_FILTER] rejected: offline`

### Test 5 — Aucun chauffeur disponible

1. Aucun chauffeur online dans 20 km
2. Client reste sur écran searching
3. Après 2 h (ou test accéléré) : dialog « Aucun chauffeur n'a accepté votre demande. »

### Vérifications MySQL

```sql
SELECT * FROM ride_offers WHERE ride_id = ? ORDER BY id;
SELECT status, driver_id, agreed_price FROM rides WHERE id = ?;
```

### Vérifications client

- `GET /rides/current?as_client=1` → `searching` puis `accepted`
- Reverb : event `RideOfferAccepted` sur `private-user-{clientId}`

---

## Compatibilité V1

- Chauffeur conserve `IncomingRideCard` pour courses `pending` (V1 legacy)
- P3 prioritaire : offres affichées avant fallback V1

---

## Documents associés

- [P3_CLIENT_DRIVER_COMPATIBILITY.md](./P3_CLIENT_DRIVER_COMPATIBILITY.md)
- [P3_DISPATCH_DEBUGGING.md](./P3_DISPATCH_DEBUGGING.md)
- [P3_DISPATCH_PROGRESS_REPORT.md](./P3_DISPATCH_PROGRESS_REPORT.md)
