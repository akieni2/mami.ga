# Validation finale P1 — MAMI Taxi V2

**Date de clôture :** 2026-06-11  
**Branche :** `feature/mami-taxi-v2-p1`  
**Commit HEAD :** `3e8ef3f`  
**Référence spec :** [MAMI_TAXI_V2.md](./MAMI_TAXI_V2.md) § Phase 1  
**Validateur :** revue code + tests automatisés + retours terrain (2 téléphones)

---

## Verdict final

| Critère | Statut |
|---------|--------|
| **Périmètre P1 complet (code)** | **OK** |
| **Stabilité client (auth / navigation)** | **OK** |
| **API estimation déployée** | **OK** |
| **Compatibilité chauffeur (V1)** | **OK** — aucun changement driver depuis `68b342b` |
| **Merge vers `main`** | **GO** |
| **Tag `v2-p1-stable`** | **GO** |

P1 est considéré **stable comme base figée** avant P2, avec les limitations et bugs ouverts documentés ci-dessous (hors périmètre P1 ou reportés P3+).

---

## 1. Périmètre P1 (rappel)

| Inclus P1 | Exclu P1 (phases ultérieures) |
|-----------|-------------------------------|
| Pickup GPS automatique | Dispatch progressif (P3) |
| Destination sur carte (tap) | Prix proposé client (P2) |
| Distance / durée / prix conseillé (API) | Paiements (P6) |
| Écran réservation V2 (`RideBookingV2Screen`) | Négociation, programmé, avis… |
| Feature flags P0 (`MAMI_TAXI_V2`, schéma rides préparé) | `MAMI_DISPATCH_V2` actif |

Le bouton **Continuer** en P1 affiche volontairement un message « phase P3 » — **aucun appel** `POST /rides/request` depuis l'écran V2.

---

## 2. Validation par domaine

### 2.1 Login

| Test | Méthode | Résultat |
|------|---------|----------|
| Connexion email/mot de passe | Device réel | **OK** — `LOGIN SUCCESS`, `AUTH STATE DATA: user=4` |
| Gestion erreur API | Revue code | **OK** — `ApiException` + SnackBar |
| Navigation post-login | Device réel | **OK** (après fix `e4b5f03`) |

### 2.2 Splash

| Test | Méthode | Résultat |
|------|---------|----------|
| Bootstrap session au démarrage | Revue code + device | **OK** — `AUTH BOOTSTRAP START` une fois |
| Délai minimum splash | Revue code | **OK** — `AppConfig.splashMinDuration` |
| Redirection login / home | Device réel | **OK** — plus de boucle infinie (`e4b5f03`) |

### 2.3 Auth

| Test | Méthode | Résultat |
|------|---------|----------|
| Token corrompu au démarrage | Fix `711a74a` | **OK** — suppression auto + `user=null` |
| `GoRouter` instancié une fois | Fix `e4b5f03` | **OK** — log `ROUTER CREATED` unique |
| Logout | Revue code | **OK** — `state = null` |
| Register | Revue code | **OK** — non testé device |

### 2.4 GPS

| Test | Méthode | Résultat |
|------|---------|----------|
| Position réelle obtenue | Device réel | **OK** — `P1 GPS obtained: 0.5331, 9.3730` |
| Permission refusée → fallback Libreville | Revue code + logs | **OK** — `P1 GPS refused — fallback Libreville` |
| Home carte centrée sur position | Device réel | **OK** — `HOME SCREEN OPENED` |
| iOS `Info.plist` permissions | Revue code | **OK** — `5709e39` |

### 2.5 Carte

| Test | Méthode | Résultat |
|------|---------|----------|
| `MamiMap` OSM plein écran (Home) | Device réel | **OK** |
| Carte réservation V2 | Fix `3e8ef3f` | **OK code** — correctif caméra NaN ; **retest device recommandé** post-APK `3e8ef3f` |
| Tap destination | Revue code | **OK** — validation `LatLngUtils` |
| Polyline pickup → destination | Revue code | **OK** — `RouteUtils.straightLine` |
| Marqueurs départ / destination | Revue code | **OK** |

### 2.6 Estimation trajet

| Test | Méthode | Résultat |
|------|---------|----------|
| `POST /api/rides/estimate` | API VPS `63.142.241.105` | **OK** — 200 + distance/durée/prix |
| `RideEstimateService` formule | Test `RideEstimateTest` | **OK** — 500 + 250×km |
| `tripEstimateProvider` client | Revue code | **OK** — logs `P1 estimate API response` |
| `TripEstimateCard` UI | Revue code | **OK** |
| Erreur réseau | Revue code | **OK** — bouton Continuer désactivé |

### 2.7 Stabilité client

| Test | Méthode | Résultat |
|------|---------|----------|
| Boucle router ↔ auth | Device + fix | **OK** — résolu `e4b5f03` |
| Crash token `BadPaddingException` | Fix | **OK** — résolu `711a74a` |
| Build APK release | CI local | **OK** — 51,3 Mo, `MAMI_TAXI_V2=true` |
| `flutter analyze` | CI local | **OK** — 0 erreur, warnings mineurs |
| Logs debug P1 | Revue code | **OK** — présents (à retirer en P9) |

### 2.8 Compatibilité chauffeur

| Test | Méthode | Résultat |
|------|---------|----------|
| Code driver modifié depuis P1 | Git | **Aucun** — dernier commit driver `68b342b` |
| APK driver release rebuild | Build `22:52` | **OK** — 51,25 Mo |
| Endpoints driver inchangés | Revue API | **OK** — `location/update`, `availability`, `rides/current` |
| Dispatch V1 backend | Revue code | **Inchangé** — compatible driver existant |
| Client V2 P1 → dispatch | Revue code | **N/A** — pas d'appel dispatch (by design) |

**Note terrain dispatch :** test client+chauffeur à < 3 m documenté dans l'audit dispatch — **hors périmètre P1** (dispatch = P3). Causes identifiées : coords NULL en base, fenêtre post-online, ou flux V2 sans `/rides/request`.

---

## 3. Fonctionnalités validées

- [x] Feature flags `MAMI_TAXI_V2` / `MAMI_DISPATCH_V2` (config + `GET /api/app/features`)
- [x] Migration schéma rides V2 (colonnes nullable, non destructif)
- [x] API `POST /api/rides/estimate`
- [x] Écran réservation GPS V2 (`RideBookingGate` → `RideBookingV2Screen`)
- [x] Pickup automatique depuis GPS
- [x] Sélection destination par tap carte
- [x] Affichage estimation (distance, durée, prix conseillé)
- [x] Splash + bootstrap session
- [x] Login / logout / navigation stable
- [x] Home + carte GPS
- [x] Fallback Libreville si GPS refusé
- [x] Tests backend `RideEstimateTest`
- [x] Documentation P0/P1 (audit, checklist, validation)

---

## 4. Fonctionnalités non encore implémentées

| Phase | Fonctionnalité |
|-------|----------------|
| **P2** | `proposed_price`, saisie prix libre client, validation min/max |
| **P3** | `RideDispatchEngine`, vagues 0–20 km, `ride_offers`, expiration 2 h |
| **P4** | Offres Reverb temps réel, first-wins |
| **P5** | Contre-proposition / négociation |
| **P6** | Paiement Cash / Airtel / Moov |
| **P7** | Réservation programmée, acompte, verrou chauffeur |
| **P7b** | Annulations motivées, no-show |
| **P8** | Avis client, rating dispatch |
| **P9** | Stabilisation, suppression logs debug, E2E complets |

**Client V2 P1 :** `POST /rides/request` depuis `RideBookingV2Screen` — **non implémenté** (prévu P3).

---

## 5. Bugs ouverts

| ID | Sévérité | Description | Phase cible |
|----|----------|-------------|-------------|
| B-01 | Moyenne | Chauffeur « En ligne » sans `latitude`/`longitude` en base (race 0–15 s) | P3 |
| B-02 | Moyenne | Erreurs GPS chauffeur silencieuses (`catch (_) {}`) | P3 |
| B-03 | Faible | `flutter analyze` : `unused_import` home_screen, `prefer_const` | P9 |
| B-04 | Faible | `dead_code` diagnostic `if (false)` dans `app_router.dart` | P9 |
| B-05 | Info | Retest device complet `/book` post-APK `3e8ef3f` non confirmé par QA | QA post-tag |

**Résolus en P1 :**

| Bug | Commit |
|-----|--------|
| Token secure storage corrompu | `711a74a` |
| Boucle infinie router ↔ auth | `e4b5f03` |
| Carte grise + destination NaN | `3e8ef3f` |
| Import `latlong2` build | `1e69583` |
| Splash bootstrap absent | `5709e39` |

---

## 6. Limitations connues

1. **Pas de dispatch** depuis l'écran réservation V2 — message SnackBar P3 uniquement.
2. **Estimation** = distance Haversine + formule fixe ; pas de routage OSRM.
3. **Pickup verrouillé** sur GPS — pas de modification manuelle du départ en P1.
4. **V1 booking** (`RideBookingScreen`) reste disponible si `MAMI_TAXI_V2=false` — dispatch V1 actif sur cet écran seulement.
5. **Chauffeur** : heartbeat GPS 10 s ; pas de foreground service Android.
6. **`MAMI_DISPATCH_V2`** : flag exposé API mais **non branché** dans le code dispatch.
7. **Logs debug** nombreux (`ROUTER`, `AUTH`, `P1`, `TOKEN`, `MAP`) — bruit console jusqu'à P9.
8. **Captures d'écran QA** non archivées dans le dépôt.

---

## 7. Commits inclus dans la clôture P1

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

## 8. Artefacts release

| Artefact | Chemin / référence |
|----------|-------------------|
| APK client P1 | `mobile/mami_client/build/app/outputs/flutter-apk/app-release.apk` |
| Build client | `--dart-define=MAMI_TAXI_V2=true`, commit `3e8ef3f` |
| APK chauffeur | `mobile/mami_driver/build/app/outputs/flutter-apk/app-release.apk` |
| API production | `http://63.142.241.105/api` |
| Tag git | `v2-p1-stable` |

---

## 9. Recommandation post-clôture

1. **P2** : démarrer sur branche dédiée depuis `main` tagué `v2-p1-stable`.
2. **QA** : compléter checklist [P1_DEPLOYMENT_CHECKLIST.md](./P1_DEPLOYMENT_CHECKLIST.md) §4–5 sur APK `3e8ef3f`.
3. **P3** : traiter audit dispatch (coords chauffeur, logs `[DISPATCH]`) avant tests terrain client+chauffeur.
4. **Backend VPS** : confirmer `php artisan migrate` à jour après merge.

---

## 10. Documents associés

- [MAMI_TAXI_V2.md](./MAMI_TAXI_V2.md) — architecture V2
- [P0_AUDIT_REPORT.md](./P0_AUDIT_REPORT.md) — fondations P0
- [P1_VALIDATION_REPORT.md](./P1_VALIDATION_REPORT.md) — validation intermédiaire (NO GO → résolu)
- [P1_DEPLOYMENT_CHECKLIST.md](./P1_DEPLOYMENT_CHECKLIST.md) — checklist QA device
- [P1_RELEASE_NOTES.md](./P1_RELEASE_NOTES.md) — notes de merge
