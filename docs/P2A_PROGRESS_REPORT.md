# Rapport d'avancement — P2A Text-first booking

**Sprint :** P2A — **CLÔTURÉ**  
**Branche :** `feature/mami-taxi-v2-p2`  
**Tag :** `v2-p2a-stable`  
**Date début :** 2026-06-12  
**Date clôture :** 2026-06-12  
**Validation :** device réel + VPS — [P2A_FINAL_VALIDATION.md](./P2A_FINAL_VALIDATION.md)

---

## Statut final

| Livrable | Statut |
|----------|--------|
| Migration `pickup_label`, `destination_label`, coords nullable | ✅ Déployé VPS |
| Statut `searching`, `driver_id` null | ✅ Validé terrain |
| `RideBookingService` + API `POST /rides/request` | ✅ Validé terrain |
| `RideBookingTextScreen` | ✅ Validé device |
| Tests `RideBookingTextTest` | ✅ |
| APK validation client | ✅ ~51,5 Mo |
| Tag `v2-p2a-stable` | ✅ |

**Verdict : GO** — P2A figé.

---

## Validation terrain (exemple production)

```
pickup_label      = Carrefour STFO
destination_label = Sni owendo
proposed_price    = 5000
payment_method    = cash
status            = searching
driver_id         = null
```

---

## Commits P2A

| Hash | Message |
|------|---------|
| `9ea4225` | `feat(v2-p2a): add text booking schema and searching status` |
| `90c5f6d` | `feat(v2-p2a): add RideBookingService and text-first API` |
| `437d7fe` | `test(v2-p2a): add text booking feature tests` |
| `b0d6fa4` | `feat(v2-p2a): add RideBookingTextScreen and client request flow` |
| `6e4e3b2` | `docs(v2-p2a): add sprint progress report` |

---

## Backend

### Migration

`2026_06_12_100000_add_text_booking_fields_to_rides_table.php`

- `pickup_label`, `destination_label` (string nullable)
- `pickup_*` / `destination_*` coords → nullable

### Service

`RideBookingService::createTextBooking()` :

- `status` = searching
- `driver_id` = null
- `booking_type` = immediate
- `proposed_price`, `payment_method` requis
- `suggested_price` si coords fournies
- `dispatch_expires_at` = +2h

### API

```json
POST /api/rides/request
{
  "pickup_label": "Carrefour STFO",
  "destination_label": "Sni owendo",
  "proposed_price": 5000,
  "payment_method": "cash"
}
```

→ 201, `status: searching`

---

## Client Flutter

- `RideBookingTextScreen` — écran principal V2 (`RideBookingGate`)
- Pas de GPS à la réservation
- Flux : formulaire → `requestTextRide()` → `/ride/searching/{id}`

---

## Hors périmètre (respecté)

- ❌ Dispatch P3
- ❌ GPS post-acceptation P4
- ❌ Paiements réels
- ❌ Notifications temps réel
- ❌ Réservations programmées
- ❌ Carte optionnelle → **P2B**

---

## Préparation P2B

**Branche de travail :** `feature/mami-taxi-v2-p2` (suite depuis tag `v2-p2a-stable`)

**Baseline figée :** ne pas modifier le contrat API text-first P2A sans versionning.

### Contraintes P2B

- Parcours texte P2A **inchangé** (régression interdite)
- Carte **facultative** uniquement
- Pas de GPS obligatoire à la réservation

### Périmètre P2B (prochain sprint)

| Tâche | Fichier(s) cible(s) |
|-------|---------------------|
| Lien « Choisir sur la carte » | `ride_booking_text_screen.dart` |
| Modal sélection carte | `ride_map_picker_sheet.dart` [NEW] |
| Enrichissement coords optionnelles | `booking_form_provider.dart`, `rides_repository.dart` |
| `suggested_price` live | `trip_estimate_provider.dart` ou appel direct estimate |
| Badge « Affiné sur carte » | `ride_booking_text_screen.dart` |
| **Analyse d'usage** — `pickup_source`, `destination_source` | migration, `LocationSource` enum, `RideBookingService` |
| Métriques : text / map / hybrid | calcul serveur auto, `RideResource`, tests |

### Analyse d'usage (`pickup_source` / `destination_source`)

| Valeur | Signification |
|--------|---------------|
| `text` | Saisie texte seule (défaut P2A) |
| `map` | Point choisi sur carte uniquement |
| `hybrid` | Texte saisi + coords via carte |

Objectif : mesurer texte pur vs carte vs hybride pour décisions produit futures. Voir [P2_IMPLEMENTATION_PLAN.md](./P2_IMPLEMENTATION_PLAN.md) § P2B — Analyse d'usage.

### Commande démarrage P2B

```bash
git checkout feature/mami-taxi-v2-p2
git pull origin feature/mami-taxi-v2-p2
# Point de départ : v2-p2a-stable
git log -1 v2-p2a-stable --oneline
```

### Critères d'acceptation P2B (rappel)

- [ ] Commander en texte seul — comportement **identique** à P2A (`pickup_source=text`, `destination_source=text`)
- [ ] Option carte pour affiner départ et/ou destination (facultatif)
- [ ] `suggested_price` affiché après sélection carte
- [ ] Labels texte conservés en base
- [ ] Sources `text` / `map` / `hybrid` correctement enregistrées par combinaison

**Référence technique :** [P2_IMPLEMENTATION_PLAN.md](./P2_IMPLEMENTATION_PLAN.md) § P2B

---

## Documents associés

- [P2A_FINAL_VALIDATION.md](./P2A_FINAL_VALIDATION.md)
- [P2_TEXT_BOOKING_PROPOSAL.md](./P2_TEXT_BOOKING_PROPOSAL.md)
- [P2_IMPLEMENTATION_PLAN.md](./P2_IMPLEMENTATION_PLAN.md)
- [P1_FINAL_VALIDATION.md](./P1_FINAL_VALIDATION.md)
