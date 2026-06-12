# Audit compatibilité P3 — Client & Chauffeur Flutter

**Date :** 2026-06-12  
**Branche :** `feature/mami-taxi-v2-p2`  
**Backend :** P3 déployé (`MAMI_DISPATCH_V2=true`, logs `[DISPATCH] Ride #10 searching` confirmés)  
**Périmètre :** audit uniquement — aucun développement Flutter effectué

---

## Verdict global

### **C. Rebuild Flutter requis avec nouveaux écrans/composants P3**

Le backend P3 fonctionne (dispatch, offres, events). Les applications Flutter **n'ont pas été mises à jour** pour le flux offres V2. Elles restent sur le modèle V1 (course `pending` pré-assignée au chauffeur).

| Application | Compatibilité P3 | Bloquant terrain |
|-------------|------------------|------------------|
| **mami_client** | Partielle (~40 %) | Acceptation client possible via polling 5 s ; pas d'expire, pas de resume, events P3 ignorés |
| **mami_driver** | **Incompatible** | Aucune offre visible, aucun accept/reject P3 possible |

**APK :** non recompilés dans cet audit — les binaires actuels ne contiennent pas le code P3. Rebuild après sprint Flutter P3.

---

## 1. mami_client

### 1.1 Écrans P3 existants

| Écran | Fichier | Statut P3 |
|-------|---------|-----------|
| Réservation text-first (P2B) | `ride_booking_text_screen.dart` | ✅ Crée course `searching` via `POST /rides/request` |
| Réservation GPS (V1) | `ride_booking_screen.dart` | ⚠️ Bloqué côté API si `MAMI_DISPATCH_V2=true` |
| Gate V1/V2 | `ride_booking_gate.dart` | ✅ Route vers text-first si `MAMI_TAXI_V2=true` |
| **Recherche chauffeur** | `ride_searching_screen.dart` | ⚠️ Partiel — poll + Reverb legacy |
| Course active | `active_ride_screen.dart` | ✅ Post-accept (arrived/start/tracking) |
| Réservation GPS V2 (P1) | `ride_booking_v2_screen.dart` | ❌ Pas de dispatch (« phase P3 ») |
| Splash / Home | `splash_screen.dart`, `home_screen.dart` | ❌ Pas de reprise course `searching` |

**Absents (planifiés P3, non créés) :**
- `dispatch_status_provider.dart`
- `DispatchStatusBanner`
- `ExpiredRideDialog`
- Bannière course en cours sur home

### 1.2 Endpoints P3 consommés

| Endpoint P3 | Consommé ? | Implémentation actuelle |
|-------------|------------|-------------------------|
| `POST /api/rides/request` | ✅ | `RidesRepository.requestTextRide()` |
| `GET /api/rides/{id}` | ✅ (substitut) | `fetchRide()` — poll toutes les 5 s |
| `GET /api/rides/current?as_client=1` | ❌ | Non implémenté |
| `POST /api/rides/{id}/cancel` | ❌ | Annulation locale uniquement |
| `GET /api/rides/offers/*` | N/A | Côté chauffeur |

**Fichier repository :** `mobile/mami_client/lib/features/rides/data/rides_repository.dart`

### 1.3 Temps réel Reverb

| Composant | Statut |
|-----------|--------|
| `ReverbService` (connexion, auth, canaux) | ✅ |
| Abonnement `private-ride-{id}`, `private-user-{userId}` | ✅ via `active_ride_provider.dart` |
| Polling fallback 5 s | ✅ |

**Events écoutés (`reverb_service.dart`) :**

```
RideRequested, RideAssigned, RideAccepted, RideArrived,
RideStarted, RideCompleted, DriverLocationUpdated
```

**Events P3 NON écoutés :**

| Event P3 backend | Impact |
|------------------|--------|
| `RideOfferAccepted` | Pas de refresh instantané à l'acceptation (poll 5 s max) |
| `RideSearchExpired` | Expiration 2 h invisible pour l'utilisateur |
| `RideOfferCreated` | N/A client |

### 1.4 Vérifications demandées — Client

| Fonctionnalité | Statut | Détail |
|----------------|--------|--------|
| Suivi course `searching` | ⚠️ Partiel | `RideSearchingScreen` affiche statut, poll `GET /rides/{id}` |
| Consultation état dispatch | ❌ | Pas de vague, pas d'offres, pas de `dispatch_expires_at` |
| Refresh temps réel | ⚠️ Partiel | Reverb legacy ; `RideOfferAccepted` ignoré ; poll 5 s compense partiellement |
| Gestion `expired` | ❌ | `RideSearchingScreen` ne réagit pas à `status=expired` |
| Reprise après fermeture app | ❌ | Pas de `GET /rides/current?as_client=1` au démarrage |
| `agreed_price` post-accept | ❌ | Non modélisé dans `RideModel` |

### 1.5 Modèles & providers

| Fichier | P3 |
|---------|-----|
| `ride_model.dart` | `isSearching` ✅ ; `isExpired` ❌ ; `agreedPrice` ❌ ; `dispatchExpiresAt` ❌ |
| `active_ride_provider.dart` | Hybrid poll + Reverb ✅ ; events P3 ❌ |
| `ride_offer_model.dart` | ❌ N'existe pas |
| `dispatch_status_provider.dart` | ❌ N'existe pas |
| `app_features.dart` | `dispatchV2Enabled` parsé mais **jamais utilisé** |

---

## 2. mami_driver

### 2.1 Écrans P3 existants

| Écran | Fichier | Statut P3 |
|-------|---------|-----------|
| Dashboard chauffeur | `home_screen.dart` | ❌ V1 — affiche offre si `ride.isPending` uniquement |
| Carte course entrante | `incoming_ride_card.dart` | ⚠️ UI existe, câblée V1 (`RideModel`, pas `RideOffer`) |
| Course active | `active_ride_screen.dart` | ✅ arrived/start/complete inchangés |
| Historique | `ride_history_screen.dart` | ✅ |

**Condition d'affichage actuelle (`home_screen.dart`) :**

```dart
else if (ride != null && ride.isPending)
  IncomingRideCard(...)
```

P3 : course en `searching`, offre dans `ride_offers` → **`isPending` toujours false** → carte jamais affichée.

### 2.2 Endpoints P3 consommés

| Endpoint P3 | Consommé ? | Implémentation actuelle |
|-------------|------------|-------------------------|
| `GET /api/rides/offers/current` | ❌ | — |
| `POST /api/rides/{ride}/offers/{offer}/accept` | ❌ | — |
| `POST /api/rides/{ride}/offers/{offer}/reject` | ❌ | — |
| `GET /api/rides/current` | ✅ V1 | `fetchCurrentRide()` — retourne `pending/accepted/...` **pas `searching`** |
| `POST /api/rides/{id}/accept` | ✅ V1 | Requiert `status=pending` + `driver_id` pré-assigné |
| `POST /api/rides/{id}/reject` | ✅ V1 | Idem |

**Fichier repository :** `mobile/mami_driver/lib/features/rides/data/rides_repository.dart`

### 2.3 Temps réel Reverb

| Composant | Statut |
|-----------|--------|
| `ReverbService` + `private-driver-{id}` | ✅ Infrastructure prête |
| Poll `GET /rides/current` 8 s | ✅ V1 uniquement |

**Events P3 NON écoutés :**

| Event | Canal backend | Impact |
|-------|---------------|--------|
| `RideOfferCreated` | `private-driver-{id}` | Chauffeur ne reçoit aucune notification d'offre |
| `RideOfferAccepted` | ride + user | Non géré (course déjà acceptée par autre) |
| `RideSearchExpired` | user | N/A chauffeur |

### 2.4 Vérifications demandées — Chauffeur

| Fonctionnalité | Statut | Détail |
|----------------|--------|--------|
| Récupération offres | ❌ | Pas d'appel `GET /rides/offers/current` |
| Affichage offre | ❌ | `IncomingRideCard` lié à `pending`, pas aux offres |
| Acceptation offre | ❌ | `POST .../offers/{offer}/accept` absent |
| Refus offre | ❌ | `POST .../offers/{offer}/reject` absent |
| Temps réel Reverb | ❌ | `RideOfferCreated` non dans `rideEvents` |
| GPS / online (prérequis) | ✅ | `location_tracker_provider`, `driver_status_provider` |

### 2.5 Modèles & providers

| Fichier | P3 |
|---------|-----|
| `ride_model.dart` | V1 — pas de `pickup_label`, `proposed_price`, `payment_method` ; coords requises au parse |
| `ride_offer_model.dart` | ❌ N'existe pas |
| `active_ride_provider.dart` | Poll `/rides/current` + Reverb V1 ; pas d'offres |
| `incoming_offer_provider.dart` | ❌ N'existe pas (planifié P3) |

---

## 3. Matrice flux P3 terrain

```
Backend P3 (déployé)                    Client actuel              Chauffeur actuel
─────────────────────                   ─────────────              ────────────────
POST /rides/request                     ✅ Crée searching          —
  → [DISPATCH] searching                ✅ Navigue searching      —
  → ride_offers créées                  — (pas visible)           ❌ Pas de fetch
  → RideOfferCreated (Reverb)           —                         ❌ Event ignoré
Chauffeur accepte offer                 ⚠️ Poll 5s détecte         ❌ Impossible
  → RideOfferAccepted                   ❌ Event ignoré            —
  → status=accepted                     ✅ Navigue active          ✅ /rides/current OK
```

**Conséquence terrain avec `MAMI_DISPATCH_V2=true` :**
- Client peut commander et attendre (écran searching).
- Chauffeur **ne voit jamais l'offre** → course reste `searching` jusqu'à expiration 2 h.
- Logs backend `[OFFER]` peuvent apparaître sans interaction chauffeur.

---

## 4. Conclusion détaillée

| Option | Applicable ? | Justification |
|--------|--------------|---------------|
| **A. Backend prêt, apps inchangées** | Partiel | Backend OK ; apps = code pré-P3 |
| **B. Applications déjà compatibles P3** | **Non** | Chauffeur incompatible ; client incomplet |
| **C. Rebuild Flutter requis** | **Oui** | Nouveaux modèles, endpoints, events, UI offres |

---

## 5. Liste des développements Flutter requis (sprint P3 UI)

### mami_client

| Priorité | Composant | Type | Description |
|----------|-----------|------|-------------|
| P0 | `RidesRepository.fetchCurrentClientRide()` | Data | `GET /rides/current?as_client=1` |
| P0 | `ReverbService` — events P3 | Realtime | Ajouter `RideOfferAccepted`, `RideSearchExpired` |
| P0 | `RideSearchingScreen` — `expired` | Écran | Dialog + retour home |
| P1 | `RideModel` enrichi | Model | `agreedPrice`, `isExpired`, `dispatchExpiresAt` |
| P1 | Reprise splash/home | Flow | Si `searching` → `/ride/searching/:id` |
| P2 | `dispatch_status_provider` | Provider | État dispatch + bannière |
| P2 | `ExpiredRideDialog` | Widget | UX expiration 2 h |
| P2 | `POST /rides/{id}/cancel` | API | Annulation recherche serveur |

### mami_driver

| Priorité | Composant | Type | Description |
|----------|-----------|------|-------------|
| P0 | `RideOfferModel` | Model | Miroir `RideOfferResource` |
| P0 | `fetchCurrentOffers()` | Data | `GET /rides/offers/current` |
| P0 | `acceptOffer()` / `rejectOffer()` | Data | Endpoints P3 |
| P0 | `pendingOffersProvider` | Provider | Poll + état offres |
| P0 | `ReverbService` — `RideOfferCreated` | Realtime | Refresh offres instantané |
| P0 | `HomeScreen` — câblage offres | Écran | Afficher `IncomingRideCard` depuis offre |
| P0 | `IncomingRideCard` refonte | Widget | Labels texte, prix, paiement, `offerId`, coords nullable |
| P1 | `RideModel` P2/P3 fields | Model | `pickup_label`, `proposed_price`, `payment_method` |
| P1 | Mode dual V1/V2 | Feature flag | Fallback si `dispatch_v2_enabled=false` |

---

## 6. APK

| Application | Rebuild requis ? | APK audit |
|-------------|------------------|-----------|
| mami_client | **Oui** | Non compilé — code P3 UI absent |
| mami_driver | **Oui** | Non compilé — code P3 UI absent |

**Commandes post-développement :**

```bash
# Client
cd mobile/mami_client
flutter build apk --release --dart-define=MAMI_TAXI_V2=true

# Chauffeur
cd mobile/mami_driver
flutter build apk --release
```

---

## 7. Tests terrain recommandés (après rebuild)

1. Client commande text-first → `searching` → écran recherche.
2. Chauffeur online à < 100 m → offre visible sur `IncomingRideCard`.
3. Chauffeur accepte → client passe `accepted` (< 2 s via Reverb).
4. Chauffeur refuse → course reste `searching`, autre vague possible.
5. Chauffeur offline → pas d'offre (`[DRIVER_FILTER]` backend).
6. Expiration 2 h → client voit dialog `expired`.

Voir [P3_DISPATCH_DEBUGGING.md](./P3_DISPATCH_DEBUGGING.md) pour diagnostic backend entre-temps.

---

## 8. Références

- [P3_DISPATCH_PROGRESS_REPORT.md](./P3_DISPATCH_PROGRESS_REPORT.md)
- [P3_DISPATCH_DEBUGGING.md](./P3_DISPATCH_DEBUGGING.md)
- [P2_IMPLEMENTATION_PLAN.md](./P2_IMPLEMENTATION_PLAN.md) § Client/Chauffeur P3
