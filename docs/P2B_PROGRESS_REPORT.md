# Rapport d'avancement — P2B Carte optionnelle

**Sprint :** P2B — **LIVRÉ (validation terrain en attente)**  
**Branche :** `feature/mami-taxi-v2-p2`  
**Baseline :** `v2-p2a-stable` (`74ec781`)  
**Date début :** 2026-06-12  
**Date livraison build :** 2026-06-12  

---

## Statut livrables

| Livrable | Statut |
|----------|--------|
| Migration `pickup_source`, `destination_source` | ✅ Code livré — migration VPS à exécuter |
| `LocationSource` + `LocationSourceResolver` | ✅ |
| `RideBookingService` (sources + estimation si coords) | ✅ |
| API `POST /rides/request` enrichie (réponse) | ✅ |
| Tests `RideBookingMapTest` + `RideBookingTextTest` | ✅ |
| `RideMapPickerSheet` + bouton carte optionnel | ✅ |
| `RideBookingTextScreen` (parcours text-first inchangé) | ✅ |
| APK validation client P2B | ✅ 51,72 Mo |
| Tag `v2-p2b-stable` | ⏳ Après validation terrain |

**Verdict technique :** GO build — validation téléphone réel requise avant tag.

---

## Build APK P2B

| Champ | Valeur |
|-------|--------|
| **Hash git exact** | `2bc3f4c7b83dc6c20a3f65c40048159f575751df` |
| **Commit court** | `2bc3f4c` |
| **Chemin APK** | `mobile/mami_client/build/app/outputs/flutter-apk/app-release.apk` |
| **Chemin absolu** | `C:\Users\LENOVO\mami.ga\mobile\mami_client\build\app\outputs\flutter-apk\app-release.apk` |
| **Taille** | 54 229 322 octets (~51,72 Mo) |
| **Date compilation** | 2026-06-12 01:39:49 |
| **Commande** | `flutter build apk --release --dart-define=MAMI_TAXI_V2=true` |

---

## Commits P2B (depuis `v2-p2a-stable`)

| Hash | Message |
|------|---------|
| `e8b6073` | `docs(p2b): add pickup_source and destination_source usage analytics to plan` |
| `2bc3f4c` | `feat(v2-p2b): add optional map picker and location sources` |

---

## Backend

### Migration

`2026_06_12_200000_add_location_sources_to_rides_table.php`

- `pickup_source` (string, défaut `text`)
- `destination_source` (string, défaut `text`)

**Déploiement VPS :**

```bash
php artisan migrate --force
```

### Résolution des sources (serveur uniquement)

`LocationSourceResolver::resolve(label, lat, lng)` :

| Cas | `pickup_source` / `destination_source` |
|-----|----------------------------------------|
| Pas de coordonnées | `text` |
| Coordonnées + label vide ou format `0.4162, 9.4673` | `map` |
| Coordonnées + label texte utilisateur | `hybrid` |

Le client **n'envoie pas** `pickup_source` ni `destination_source`.

### Estimation

Si les 4 coordonnées sont présentes → `suggested_price`, `distance_km`, `duration_minutes` calculés.  
Sinon → comportement P2A inchangé (champs null).

### API

```json
POST /api/rides/request
{
  "pickup_label": "Carrefour STFO",
  "destination_label": "Sni owendo",
  "proposed_price": 5000,
  "payment_method": "cash",
  "pickup_latitude": 0.4162,
  "pickup_longitude": 9.4673,
  "destination_latitude": 0.3900,
  "destination_longitude": 9.4500
}
```

Réponse enrichie :

```json
{
  "pickup_source": "hybrid",
  "destination_source": "hybrid",
  "suggested_price": 3200,
  "distance_km": 4.2,
  "duration_minutes": 12
}
```

---

## Client Flutter

### Fichiers P2B

| Fichier | Rôle |
|---------|------|
| `ride_booking_text_screen.dart` | Écran principal — text-first + bouton carte optionnel |
| `ride_map_picker_sheet.dart` | Modal `MamiMap`, sélection départ/destination |
| `ride_model.dart` | Champs `pickupSource`, `destinationSource` |

### Parcours utilisateur

1. **Texte seul (P2A)** — Départ → Destination → Prix → Paiement → Rechercher un chauffeur. Aucune carte, aucun GPS.
2. **Carte optionnelle** — Bouton « Choisir sur la carte » → placer départ et/ou destination → badge « Affiné sur la carte » → estimation si les deux points sont posés.

---

## Tests automatisés

| Fichier | Scénarios |
|---------|-----------|
| `RideBookingTextTest` | Texte seul → `pickup_source=text`, `destination_source=text` |
| `RideBookingMapTest` | Texte seul, hybride (texte + coords), carte seule (labels coords) |

```bash
php artisan test --filter=RideBooking
```

---

## Hors périmètre (respecté)

- ❌ Dispatch P3
- ❌ GPS post-acceptation
- ❌ Paiement réel
- ❌ Réservation programmée
- ❌ Notifications temps réel

---

## Procédure de test terrain (téléphone réel)

### Prérequis

1. VPS synchronisé sur `2bc3f4c` (ou plus récent sur `feature/mami-taxi-v2-p2`).
2. Migration exécutée : `php artisan migrate --force`.
3. APK installé : `app-release.apk` (hash `2bc3f4c7b83dc6c20a3f65c40048159f575751df`).
4. Compte client actif, API `https://api.mami.ga/api`.
5. Connexion internet stable.

### Installation APK

```bash
adb install -r mobile/mami_client/build/app/outputs/flutter-apk/app-release.apk
```

Ou transfert manuel + installation « sources inconnues » autorisées.

---

### Test 1 — Régression P2A (texte seul, obligatoire)

**Objectif :** confirmer que le parcours validé P2A fonctionne sans carte ni GPS.

| Étape | Action | Résultat attendu |
|-------|--------|------------------|
| 1 | Ouvrir l'app → écran réservation | Formulaire texte visible |
| 2 | Départ : `Carrefour STFO` | Champ accepté |
| 3 | Destination : `Sni owendo` | Champ accepté |
| 4 | Prix : `5000` FCFA | Champ accepté |
| 5 | Paiement : `Espèces` | Sélection OK |
| 6 | **Ne pas** toucher « Choisir sur la carte » | Aucune interaction carte |
| 7 | Appuyer « Rechercher un chauffeur » | Navigation vers écran recherche |
| 8 | Vérifier API / base (optionnel) | `status=searching`, `driver_id=null`, `pickup_source=text`, `destination_source=text`, coords null |

**Critère GO :** identique au scénario P2A validé.

---

### Test 2 — Hybride (texte + carte)

**Objectif :** affiner les points sur carte tout en conservant les labels texte.

| Étape | Action | Résultat attendu |
|-------|--------|------------------|
| 1 | Saisir Départ : `Carrefour STFO` | Texte conservé |
| 2 | Saisir Destination : `Sni owendo` | Texte conservé |
| 3 | Appuyer « Choisir sur la carte » | Modal carte s'ouvre |
| 4 | Sélectionner « Départ », toucher la carte | Marqueur départ posé |
| 5 | Sélectionner « Destination », toucher la carte | Marqueur destination posé |
| 6 | « Confirmer les points » | Retour formulaire |
| 7 | Vérifier badges | « Affiné sur la carte » sous départ et destination |
| 8 | Vérifier prix suggéré | Valeur affichée (barre de progression puis montant) |
| 9 | Prix proposé : ex. `5000`, paiement `Espèces` | OK |
| 10 | « Rechercher un chauffeur » | Course créée |
| 11 | Vérifier API | `pickup_source=hybrid`, `destination_source=hybrid`, coords non null, `suggested_price` > 0 |

**Critère GO :** réservation réussie avec sources hybrides.

---

### Test 3 — Carte seule (optionnel)

**Objectif :** valider le mode `map` quand seuls les points carte sont utilisés.

| Étape | Action | Résultat attendu |
|-------|--------|------------------|
| 1 | Laisser champs texte vides ou effacer | — |
| 2 | « Choisir sur la carte » → placer départ et destination | Coordonnées remplies |
| 3 | Confirmer | Labels auto-remplis format `lat, lng` |
| 4 | Compléter prix + paiement → commander | Course créée |
| 5 | Vérifier API | `pickup_source=map`, `destination_source=map` |

---

### Test 4 — Carte partielle (optionnel)

**Objectif :** un seul point affiné sur carte.

| Étape | Action | Résultat attendu |
|-------|--------|------------------|
| 1 | Texte départ + destination saisis | — |
| 2 | Carte : placer **uniquement** le départ | Badge départ seulement |
| 3 | Commander | `pickup_source=hybrid` ou `text` selon coords ; `destination_source=text` si pas de coords destination |

---

### Vérification API (curl)

Remplacer `TOKEN` par le token Sanctum du client :

```bash
curl -s -X POST https://api.mami.ga/api/rides/request \
  -H "Authorization: Bearer TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"pickup_label":"Carrefour STFO","destination_label":"Sni owendo","proposed_price":5000,"payment_method":"cash"}' \
  | jq '.data | {pickup_source, destination_source, pickup_latitude, suggested_price, status}'
```

---

### Critères d'acceptation P2B

- [ ] Test 1 (texte seul) — comportement **identique** à P2A
- [ ] Test 2 (hybride) — sources `hybrid`, estimation affichée
- [ ] Aucune permission GPS exigée à la réservation
- [ ] Bouton carte **optionnel** — parcours complet sans carte
- [ ] `status=searching`, `driver_id=null` après commande

**Après validation :** taguer `v2-p2b-stable` sur `2bc3f4c` (ou commit de clôture).

---

## Documents associés

- [P2A_PROGRESS_REPORT.md](./P2A_PROGRESS_REPORT.md)
- [P2A_FINAL_VALIDATION.md](./P2A_FINAL_VALIDATION.md)
- [P2_IMPLEMENTATION_PLAN.md](./P2_IMPLEMENTATION_PLAN.md)
