# Checklist déploiement & QA — MAMI Taxi V2 P1

**Branche :** `feature/mami-taxi-v2-p1`  
**APK :** `mobile/mami_client/build/app/outputs/flutter-apk/app-release.apk`  
**API :** `https://api.mami.ga/api`

---

## 1. Build APK

```bash
cd mobile/mami_client
flutter pub get
flutter build apk --release --dart-define=MAMI_TAXI_V2=true
```

| # | Vérification | OK |
|---|--------------|-----|
| 1.1 | Build sans erreur | ☐ |
| 1.2 | APK généré (~51 Mo) | ☐ |
| 1.3 | `MAMI_TAXI_V2=true` actif | ☐ |

---

## 2. Installation Android

| # | Étape | OK |
|---|-------|-----|
| 2.1 | Transférer `app-release.apk` sur le téléphone | ☐ |
| 2.2 | Autoriser sources inconnues si besoin | ☐ |
| 2.3 | Installer / mettre à jour l'app | ☐ |
| 2.4 | Se connecter (`client@mami.ga` ou compte test) | ☐ |
| 2.5 | Accueil → **Commander une course** → écran `/book` V2 | ☐ |

**USB (optionnel) :**

```bash
adb install -r build/app/outputs/flutter-apk/app-release.apk
```

---

## 3. Vérification GPS (position obtenue)

| # | Test | Résultat attendu | OK |
|---|------|------------------|-----|
| 3.1 | GPS activé + permission accordée | Carte centrée sur position réelle | ☐ |
| 3.2 | Libellé départ | « Départ : votre position GPS » | ☐ |
| 3.3 | Log console | `P1 GPS obtained: lat, lng` | ☐ |
| 3.4 | Marqueur bleu position | Visible sur la carte | ☐ |

---

## 4. Vérification estimation

| # | Test | Résultat attendu | OK |
|---|------|------------------|-----|
| 4.1 | Tap destination sur carte | Marqueur rouge + polyline jaune | ☐ |
| 4.2 | Log console | `P1 destination selected: …` | ☐ |
| 4.3 | Carte estimation | Distance, durée, prix conseillé (FCFA) | ☐ |
| 4.4 | Log console | `P1 estimate API response: …` | ☐ |
| 4.5 | Bouton **Continuer** | Actif après estimation OK | ☐ |
| 4.6 | Tap Continuer | SnackBar « phase P3 » (pas de dispatch) | ☐ |

**Référence API (trajet moyen Libreville) :**

```bash
POST /api/rides/estimate
→ distance_km ≈ 3.491, duration_minutes ≈ 9, suggested_price ≈ 1373
```

---

## 5. Vérification réseau indisponible

| # | Test | Résultat attendu | OK |
|---|------|------------------|-----|
| 5.1 | Sélectionner destination avec réseau OK | Estimation affichée | ☐ |
| 5.2 | Activer mode avion | — | ☐ |
| 5.3 | Changer destination (nouveau tap) | Message rouge « Estimation indisponible » | ☐ |
| 5.4 | Log console | `P1 estimate API error: …` | ☐ |
| 5.5 | Bouton Continuer | Désactivé | ☐ |
| 5.6 | Carte + marqueurs | Toujours visibles | ☐ |

---

## 6. Vérification permission GPS refusée

| # | Test | Résultat attendu | OK |
|---|------|------------------|-----|
| 6.1 | Réinstaller ou révoquer permission localisation | — | ☐ |
| 6.2 | Refuser la permission | — | ☐ |
| 6.3 | Libellé départ | « Position GPS indisponible — carte centrée sur Libreville » | ☐ |
| 6.4 | Log console | `P1 GPS refused — fallback Libreville` | ☐ |
| 6.5 | Carte | Centrée Libreville (~0.4162, 9.4673) | ☐ |
| 6.6 | Destination + estimation | Fonctionne (pickup = fallback) | ☐ |

---

## 7. Backend VPS (post-déploiement)

```bash
cd /var/www/mami.ga
php artisan migrate --pretend
php artisan migrate --force
php artisan test --filter=RideEstimateTest
```

| # | Endpoint | HTTP attendu | OK |
|---|----------|--------------|-----|
| 7.1 | `GET /api/app/features` | 200 | ☐ |
| 7.2 | `POST /api/rides/estimate` (auth) | 200 | ☐ |

---

## 8. Captures à joindre

1. Écran booking GPS réel + destination + estimation  
2. Permission refusée (message Libreville)  
3. Mode avion (erreur estimation)  
4. SnackBar Continuer (phase P3)

---

## 9. Critères GO merge `main`

- [ ] Checklist §3–6 complète sur téléphone réel  
- [ ] API §7 validée  
- [ ] Au moins 3 captures  
- [ ] `flutter test` + `flutter analyze` sans erreur
