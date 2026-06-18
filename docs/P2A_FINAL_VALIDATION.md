# Validation finale P2A — Réservation Text-first

**Date de clôture :** 2026-06-12  
**Branche :** `feature/mami-taxi-v2-p2`  
**Tag :** `v2-p2a-stable`  
**Commit :** voir `git rev-parse v2-p2a-stable`  
**Référence :** [P2A_PROGRESS_REPORT.md](./P2A_PROGRESS_REPORT.md), [P2_IMPLEMENTATION_PLAN.md](./P2_IMPLEMENTATION_PLAN.md)

---

## Verdict

| Critère | Statut |
|---------|--------|
| Création course textuelle (device) | **OK** |
| Enregistrement base (VPS) | **OK** |
| Statut `searching`, `driver_id` null | **OK** |
| GPS non requis à la réservation | **OK** |
| Tag `v2-p2a-stable` | **GO** |
| Démarrage P2B | **GO** |

**P2A est figé et validé** — base stable avant carte optionnelle (P2B).

---

## Validation terrain — cas réel validé

| Champ | Valeur validée |
|-------|----------------|
| `pickup_label` | Carrefour STFO |
| `destination_label` | Sni owendo |
| `proposed_price` | 5000 |
| `payment_method` | cash |
| `status` | searching |
| `driver_id` | null |
| Coords GPS | null (non requises) |

### Parcours device

1. Login client
2. **Commander une course** → `RideBookingTextScreen`
3. Saisie départ / destination / prix / Cash
4. **Rechercher un chauffeur**
5. Écran recherche avec trajet texte + statut `searching`
6. **Aucune demande GPS** à la réservation

### Validation VPS

- Migration `2026_06_12_100000_add_text_booking_fields_to_rides_table` appliquée
- `POST /api/rides/request` text-first → 201
- Ligne `rides` conforme au cas ci-dessus en base

---

## Fonctionnalités validées P2A

- [x] Écran `RideBookingTextScreen` (départ, destination, prix, paiement)
- [x] Paiement Cash / Airtel Money / Moov Money (sélection UI)
- [x] `RideBookingService::createTextBooking()`
- [x] Statut `RideStatus::Searching`
- [x] `booking_type = immediate`
- [x] `driver_id = null` (pas de dispatch)
- [x] `pickup_label`, `destination_label` en base
- [x] Coordonnées GPS facultatives (null autorisé)
- [x] `dispatch_expires_at` renseigné (+2h, préparation P3)
- [x] Navigation client → `/ride/searching/{id}`
- [x] Tests `RideBookingTextTest` (backend)

---

## Hors périmètre P2A (non livré, conforme au plan)

| Élément | Phase cible |
|---------|-------------|
| Dispatch chauffeur | P3 |
| Carte optionnelle | P2B |
| GPS post-acceptation | P4 |
| Paiements réels / MM | P6+ |
| Notifications Reverb nouvelles | P3/P4 |
| Réservations programmées | P7 |

---

## Commits P2A

```
9ea4225 feat(v2-p2a): add text booking schema and searching status
90c5f6d feat(v2-p2a): add RideBookingService and text-first API
437d7fe test(v2-p2a): add text booking feature tests
b0d6fa4 feat(v2-p2a): add RideBookingTextScreen and client request flow
6e4e3b2 docs(v2-p2a): add sprint progress report
```

---

## Artefacts

| Artefact | Détail |
|----------|--------|
| APK client | `mobile/mami_client/build/app/outputs/flutter-apk/app-release.apk` |
| Build | `--dart-define=MAMI_TAXI_V2=true`, ~51,5 Mo |
| API | `https://api.mami.ga/api` |

---

## Limitations connues (acceptées pour P2A)

1. Aucun chauffeur notifié — recherche bloquée en `searching` jusqu'à P3.
2. Pas de `suggested_price` sans coords (normal en text-only).
3. `RideBookingV2Screen` (GPS-first P1) conservé mais non utilisé par le gate V2.
4. Annulation recherche : bouton UI présent, API cancel dédiée en P3.

---

## Prochaine étape — P2B

Carte optionnelle « Choisir sur la carte » sur `RideBookingTextScreen` :

- Modal `RideMapPickerSheet`
- Enrichissement coords optionnelles + `suggested_price`
- Branche : `feature/mami-taxi-v2-p2` (suite directe depuis `v2-p2a-stable`)

Voir [P2A_PROGRESS_REPORT.md](./P2A_PROGRESS_REPORT.md) § Préparation P2B.
