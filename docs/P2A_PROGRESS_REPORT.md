# Rapport d'avancement — P2A Text-first booking

**Sprint :** P2A  
**Branche :** `feature/mami-taxi-v2-p2`  
**Date :** 2026-06-12  
**Objectif :** création réelle de course text-first sans GPS obligatoire

---

## Livrables

| Livrable | Statut |
|----------|--------|
| Migration `pickup_label`, `destination_label`, coords nullable | ✅ |
| Statut `searching`, `driver_id` null | ✅ |
| `RideBookingService` + API `POST /rides/request` | ✅ |
| `RideBookingTextScreen` | ✅ |
| Tests `RideBookingTextTest` | ✅ (à exécuter sur VPS/CI avec PHP) |
| APK validation client | ✅ (voir ci-dessous) |

---

## Commits

| Hash | Message |
|------|---------|
| *(voir `git log`)* | `feat(v2-p2a): add text booking schema and searching status` |
| | `feat(v2-p2a): add RideBookingService and text-first API` |
| | `test(v2-p2a): add text booking feature tests` |
| | `feat(v2-p2a): add RideBookingTextScreen and client request flow` |
| | `docs(v2-p2a): add sprint progress report` |

---

## Backend

### Migration

`2026_06_12_100000_add_text_booking_fields_to_rides_table.php`

- `pickup_label`, `destination_label` (string nullable)
- `pickup_*` / `destination_*` coords → nullable

### Enum

`RideStatus::Searching = 'searching'`

### Service

`RideBookingService::createTextBooking()` :

- `status` = searching
- `driver_id` = null
- `booking_type` = immediate
- `proposed_price`, `payment_method` requis
- `suggested_price` si coords fournies
- `dispatch_expires_at` = +2h (préparation P3)

### API

`POST /api/rides/request` avec body :

```json
{
  "pickup_label": "Lalala, rond-point Total",
  "destination_label": "Nzeng-Ayong, marché",
  "proposed_price": 3000,
  "payment_method": "cash"
}
```

Réponse 201, `status: searching`, coords null autorisées.

Rétrocompat V1 : payload GPS sans labels → `RideDispatchService` (inchangé).

---

## Client Flutter

### Écran principal V2

`RideBookingTextScreen` remplace `RideBookingV2Screen` dans `RideBookingGate`.

Champs : départ, destination, prix, paiement (Cash / Airtel / Moov).

Bouton : **Rechercher un chauffeur** → `POST /rides/request` → `/ride/searching/{id}`.

**Aucun appel GPS** au chargement.

### Fichiers ajoutés

- `ride_booking_text_screen.dart`
- `payment_method.dart`
- `payment_method_selector.dart`
- `price_input_field.dart`

### Fichiers modifiés

- `ride_model.dart` — labels, prix, paiement, coords nullable
- `rides_repository.dart` — `requestTextRide()`
- `ride_searching_screen.dart` — affichage trajet texte
- `ride_booking_gate.dart`
- `active_ride_screen.dart` — garde coords null

---

## Hors périmètre (respecté)

- ❌ Dispatch P3
- ❌ GPS post-acceptation P4
- ❌ Paiements réels
- ❌ Notifications temps réel nouvelles
- ❌ Réservations programmées
- ❌ Carte optionnelle (P2B)

---

## Tests

```bash
php artisan test --filter=RideBookingTextTest
```

Cas couverts :

1. Création text-only → searching, driver null
2. Coords optionnelles → suggested_price
3. Prix invalide → 422
4. Labels courts → 422

---

## QA device

1. Désinstaller / installer APK P2A
2. Login client
3. Commander → formulaire texte (pas de demande GPS)
4. Remplir départ, destination, prix, Cash
5. **Rechercher un chauffeur**
6. Vérifier écran recherche avec labels + prix
7. Confirmer en base : `status=searching`, `driver_id=null`

---

## Prochaine étape

**P2B** — carte optionnelle « Choisir sur la carte » puis **P3** dispatch.
