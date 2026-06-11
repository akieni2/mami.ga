# Notes de release — MAMI Taxi V2 P1

**Tag :** `v2-p1-stable`  
**Date :** 2026-06-11  
**Merge :** `feature/mami-taxi-v2-p1` → `main`  
**Commit :** `3e8ef3f` (+ docs clôture)

---

## Résumé

Première release stable de la **Phase 1 MAMI Taxi V2** : réservation client avec pickup GPS automatique, destination sur carte, et estimation serveur (distance, durée, prix conseillé). **Sans dispatch** — la recherche chauffeur reste en Phase 3.

Cette release fige une base client + backend prête pour la **Phase 2** (prix proposé).

---

## Ce qui est livré

### Backend

- Feature flags `MAMI_TAXI_V2`, `MAMI_DISPATCH_V2` (`config/mami.php`, `MamiFeatures`)
- Endpoint `GET /api/app/features`
- Endpoint `POST /api/rides/estimate` (`RideEstimateService`)
- Migration additive `rides` (champs V2 nullable, `booking_type` default `immediate`)
- Test `RideEstimateTest`

### Client (`mami_client`)

- `RideBookingV2Screen` — carte OSM, tap destination, polyline, estimation API
- `RideBookingGate` — bascule V1/V2 selon feature flag
- `user_location_provider` — GPS + fallback Libreville
- Correctifs auth : token corrompu, boucle router, splash bootstrap
- Correctif carte réservation : caméra NaN / destination invalide
- Logs diagnostic P1 (GPS, estimation, router, auth)

### Chauffeur (`mami_driver`)

- **Aucune modification fonctionnelle** dans cette release
- Compatible avec le backend mergé (dispatch V1 inchangé)

### Documentation

- `docs/P0_AUDIT_REPORT.md`
- `docs/P1_VALIDATION_REPORT.md`
- `docs/P1_DEPLOYMENT_CHECKLIST.md`
- `docs/P1_FINAL_VALIDATION.md`
- `docs/MAMI_TAXI_V2.md` (spec)

---

## Correctifs critiques inclus

| Commit | Description |
|--------|-------------|
| `711a74a` | Récupération token secure storage corrompu (`BadPaddingException`) |
| `e4b5f03` | Fin boucle infinie `GoRouter` ↔ `authStateProvider` |
| `3e8ef3f` | Carte réservation grise + `Destination: NaN, NaN` |
| `5709e39` | Restauration bootstrap splash + permission iOS |
| `1e69583` | Import `latlong2` — build APK |

---

## Déploiement

### Backend (VPS)

```bash
cd /var/www/mami.ga
git pull origin main
git checkout v2-p1-stable   # ou rester sur main après merge
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

Variables `.env` recommandées :

```env
MAMI_TAXI_V2=true
MAMI_DISPATCH_V2=false
```

### Client Android

```bash
cd mobile/mami_client
flutter pub get
flutter build apk --release --dart-define=MAMI_TAXI_V2=true
```

APK : `build/app/outputs/flutter-apk/app-release.apk`

### Chauffeur Android

APK existant (`68b342b`) compatible. Rebuild optionnel :

```bash
cd mobile/mami_driver
flutter build apk --release
```

---

## API — nouveaux endpoints

| Méthode | Route | Auth | Description |
|---------|-------|------|-------------|
| `GET` | `/api/app/features` | Non | Flags V2 + tarifs |
| `POST` | `/api/rides/estimate` | Sanctum | Distance, durée, prix conseillé |

**Exemple estimate :**

```bash
curl -X POST http://63.142.241.105/api/rides/estimate \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "pickup_latitude": 0.4162,
    "pickup_longitude": 9.4673,
    "destination_latitude": 0.3900,
    "destination_longitude": 9.4500
  }'
```

Réponse attendue : `distance_km ≈ 3.491`, `duration_minutes ≈ 9`, `suggested_price ≈ 1373` FCFA.

---

## Non inclus (phases futures)

- Dispatch progressif (`MAMI_DISPATCH_V2`)
- `POST /rides/request` depuis écran V2
- Prix proposé client (P2)
- Paiements, programmé, négociation, avis

---

## Limitations connues

Voir [P1_FINAL_VALIDATION.md](./P1_FINAL_VALIDATION.md) §6.

Points principaux :

- Bouton **Continuer** V2 = aperçu uniquement (SnackBar « phase P3 »)
- Logs debug encore présents
- Test dispatch terrain client+chauffeur = sujet P3, pas bloquant P1

---

## Validation

| Domaine | Statut |
|---------|--------|
| Login / auth / splash | Validé device |
| Home + GPS | Validé device |
| API estimate | Validé VPS |
| Carte réservation V2 | Validé code + fix `3e8ef3f` |
| Build APK client | OK |
| Compatibilité chauffeur | OK (pas de breaking change) |

---

## Commits mergés (`314a923`..`3e8ef3f`)

```
5e84900 feat(v2-p0): add feature flags and ride schema preparation
ff0bb01 docs(v2-p0): add technical audit report
6d927aa feat(v2-p1): add trip estimate API and app features endpoint
78d1178 feat(v2-p1): add GPS booking screen with server-side trip estimate
5709e39 fix(client): restore splash bootstrap and iOS location permission
b9ed85c docs(v2-p1): add validation report with NO GO recommendation
b4e9566 fix(v2-p1): GPS fallback UX, P1 logs, CI test, and QA checklist
1e69583 fix(client): correct latlong2 import path for build
711a74a fix(auth): recover from corrupted secure storage token
e4b5f03 fix(client): stop router-auth infinite redirect loop
3e8ef3f fix(client): prevent NaN map camera on booking screen
```

---

## Prochaine étape

**Phase 2** — prix proposé client (`proposed_price`, UI saisie, validation API).

Branche suggérée : `feature/mami-taxi-v2-p2` depuis `main` @ `v2-p1-stable`.
