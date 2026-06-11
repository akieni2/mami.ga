# Rapport de validation P1 — MAMI Taxi V2

**Branche :** `feature/mami-taxi-v2-p1` (+ hotfix)  
**Date :** 2026-06-11 (mis à jour post-déploiement VPS)  
**Validateur :** Agent CI / audit automatisé + revue code  
**Référence :** [MAMI_TAXI_V2.md](./MAMI_TAXI_V2.md), [P0_AUDIT_REPORT.md](./P0_AUDIT_REPORT.md)

---

## Verdict

| Critère | Statut |
|---------|--------|
| **Merge vers `main`** | **NO GO** (conditionnel) |
| **Déploiement production** | **NO GO** |

### Conditions pour passer en GO

1. Déployer le backend de la branche sur le VPS + `php artisan migrate`.
2. Valider `POST /api/rides/estimate` sur l’environnement cible (3 trajets).
3. Installer l’APK sur téléphone réel et compléter la checklist UI (captures).
4. Corriger ou exclure `test/widget_test.dart` (référence `MyApp` obsolète).

---

## 1. Tests sur téléphone réel

| Élément | Résultat | Détail |
|---------|----------|--------|
| Build APK release | **OK** | `app-release.apk` (51,2 Mo) généré avec `--dart-define=MAMI_TAXI_V2=true` |
| ADB / installation | **NON EXÉCUTÉ** | `adb` absent du PATH Windows |
| Captures d’écran | **NON DISPONIBLES** | Nécessite exécution manuelle sur appareil |

### Procédure manuelle recommandée

```bash
cd mobile/mami_client
flutter build apk --release --dart-define=MAMI_TAXI_V2=true
# Transférer build/app/outputs/flutter-apk/app-release.apk sur le téléphone
# Activer USB debugging OU partage fichier + installer
```

---

## 2. Checklist fonctionnelle UI (revue code + statut)

| # | Test | Revue code | Test device |
|---|------|------------|-------------|
| 1 | GPS récupéré automatiquement | **OK** — `user_location_provider` + `_initPickupFromGps()` | **À FAIRE** |
| 2 | Carte affichée | **OK** — `MamiMap` OSM fullScreen | **À FAIRE** |
| 3 | Marqueur départ | **OK** — `pickup` + `user` markers | **À FAIRE** |
| 4 | Sélection destination par tap | **OK** — `_onMapTap` | **À FAIRE** |
| 5 | Marqueur destination | **OK** — `destination` marker rouge | **À FAIRE** |
| 6 | Polyline visible | **OK** — `RouteUtils.straightLine` + PolylineLayer | **À FAIRE** |
| 7 | Distance estimée | **OK** — `TripEstimateCard` via API | **À FAIRE** |
| 8 | Durée estimée | **OK** — idem | **À FAIRE** |
| 9 | Prix conseillé | **OK** — `suggested_price` API | **À FAIRE** |
| 10 | Pas de dispatch | **OK** — bouton → SnackBar P3 uniquement | **À FAIRE** |

---

## 3. Cas limites

### 3.1 GPS désactivé / permission refusée

**Comportement code (`user_location_provider.dart`) :**

- Permission refusée → fallback **Libreville** `LatLng(0.4162, 9.4673)`.
- La carte s’affiche ; le départ utilise le fallback (pas de blocage).
- **Hotfix :** message « Position GPS indisponible — carte centrée sur Libreville » si permission refusée.

| Test | Revue | Device |
|------|-------|--------|
| GPS désactivé | Fallback Libreville | **À FAIRE** |
| Permission refusée | Fallback Libreville | **À FAIRE** |

### 3.2 Réseau indisponible

**Comportement code (`ride_booking_v2_screen.dart`) :**

- `tripEstimateProvider` en erreur → texte rouge `Estimation indisponible : …`
- Bouton **Continuer** désactivé (`estimateAsync?.hasValue != true`).
- Carte et marqueurs restent fonctionnels (hors ligne).

| Test | Revue | Device |
|------|-------|--------|
| Mode avion après destination | **OK** (erreur affichée) | **À FAIRE** |

---

## 4. Migration `php artisan migrate --pretend`

| Élément | Résultat |
|---------|----------|
| Exécution locale | **NON EXÉCUTÉ** — PHP absent du PATH sur la machine de validation |
| Analyse statique migration | **OK** — voir ci-dessous |

### Analyse statique `2026_05_25_100000_add_v2_prepared_fields_to_rides_table.php`

| Vérification | Résultat |
|--------------|----------|
| Modification colonnes existantes | **Aucune** |
| Suppression colonnes | **Aucune** (uniquement dans `down()`) |
| Nouvelles colonnes | **Toutes nullable** sauf `booking_type` DEFAULT `'immediate'` |
| Impact lignes existantes | `booking_type = 'immediate'` par défaut — **compatible V1** |
| Index ajoutés | `(scheduled_at, status)`, `(dispatch_expires_at, status)` |

**SQL attendu (résumé) :**

```sql
ALTER TABLE rides ADD booking_type VARCHAR(255) NOT NULL DEFAULT 'immediate' AFTER status;
ALTER TABLE rides ADD scheduled_at TIMESTAMP NULL, ...;
-- + 18 colonnes nullable
CREATE INDEX rides_scheduled_at_status_index ON rides (scheduled_at, status);
CREATE INDEX rides_dispatch_expires_at_status_index ON rides (dispatch_expires_at, status);
```

**Conclusion migration :** aucune colonne existante cassée ; ajouts non destructifs.

**Action requise sur VPS après merge :**

```bash
cd /var/www/mami.ga
php artisan migrate --pretend   # vérification
php artisan migrate --force
```

---

## 5. API `POST /api/rides/estimate`

### Production (`63.142.241.105`) — backend P0/P1 déployé

| Endpoint | Statut |
|----------|--------|
| `GET /api/app/features` | **200** — `taxi_v2_enabled`, tarifs, flags |
| `POST /api/rides/estimate` | **200** — estimation distance / durée / prix (auth Sanctum) |

**Login API :** OK (`client@mami.ga` → token Sanctum).

### Valeurs attendues (calcul `RideEstimateService` — formule validée)

| Trajet | Pickup | Destination | distance_km | duration_min | suggested_price (FCFA) |
|--------|--------|-------------|-------------|--------------|--------------------------|
| **Court** | 0.4162, 9.4673 | 0.4180, 9.4690 | **0.275** | **1** | **569** |
| **Moyen** | 0.4162, 9.4673 | 0.3900, 9.4500 | **3.491** | **9** | **1373** |
| **Long** | 0.4162, 9.4673 | 0.3500, 9.5000 | **8.210** | **20** | **2553** |

Formule : `suggested_price = 500 + distance_km × 250` ; durée = `ceil(distance / 25 km/h × 60)`.

### Test automatisé ajouté

- `tests/Feature/RideEstimateTest.php` — à exécuter après déploiement :

```bash
php artisan test --filter=RideEstimateTest
```

---

## 6. Analyse statique Flutter

```
flutter analyze mami_client
```

| Sévérité | Fichier | Issue |
|----------|---------|-------|
| — | `test/widget_test.dart` | **Corrigé** — test unitaire `TripEstimate.fromJson` |
| warning | `app_router.dart` | dead_code (diagnostic `if (false)`) |
| info | divers | `prefer_const_constructors` |

**Fichiers P1 :** aucune erreur sur `ride_booking_v2_screen.dart`, `trip_estimate_*`, `app_features_*`.

---

## 7. Logs attendus (device)

Logs P1 (hotfix) :

```
P1 GPS obtained: lat, lng
P1 GPS refused — fallback Libreville
P1 destination selected: lat, lng
P1 estimate API response: distance=… duration=… price=…
P1 estimate API error: …
```

---

## 8. Captures d'écran

**Non produites** dans cet environnement (pas d’émulateur / appareil connecté).

### Scènes à capturer manuellement

1. Écran `/book` — carte centrée GPS Libreville ou position réelle  
2. Après tap destination — polyline jaune + 2 marqueurs  
3. Carte estimation — distance / durée / prix  
4. Permission refusée — carte avec fallback  
5. Mode avion — message erreur estimation  

---

## 9. Recommandation finale

### NO GO merge `main` — jusqu’à :

| # | Action | Responsable |
|---|--------|-------------|
| 1 | Merger ou déployer la branche sur VPS staging | DevOps |
| 2 | `php artisan migrate --pretend` puis `migrate` | DevOps |
| 3 | Tester 3 trajets `POST /api/rides/estimate` live | QA |
| 4 | Installer APK sur téléphone + checklist §2 | QA |
| 5 | Fournir 3–5 captures | QA |
| 6 | (Optionnel) Corriger `widget_test.dart` | Dev |

### GO merge `main` — si :

- L’équipe accepte de merger le **code** sans validation device, **à condition** que le déploiement backend + QA device soit fait **immédiatement après** sur staging avant prod.

### Points positifs (prêts pour merge code)

- Architecture P1 conforme au spec (pas de dispatch / paiement).
- Migration additive sûre.
- Estimation serveur cohérente (formule vérifiée).
- Gestion erreur réseau présente.
- Splash bootstrap restauré.

### Points bloquants merge

- Validation téléphone réelle non effectuée (checklist : [P1_DEPLOYMENT_CHECKLIST.md](./P1_DEPLOYMENT_CHECKLIST.md)).
- Captures d’écran à fournir.

---

## 10. Commandes de re-test rapide

```bash
# Backend (sur VPS avec PHP)
php artisan migrate --pretend
php artisan test --filter=RideEstimateTest

# API live (après déploiement)
TOKEN=$(curl -s -X POST http://63.142.241.105/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"client@mami.ga","password":"password"}' | jq -r '.data.token')

curl -s -X POST http://63.142.241.105/api/rides/estimate \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"pickup_latitude":0.4162,"pickup_longitude":9.4673,"destination_latitude":0.3900,"destination_longitude":9.4500}'

# Client
cd mobile/mami_client
flutter build apk --release --dart-define=MAMI_TAXI_V2=true
flutter install   # avec appareil USB connecté
```
